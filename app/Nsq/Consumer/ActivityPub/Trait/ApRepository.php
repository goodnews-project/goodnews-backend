<?php

declare(strict_types=1);

namespace App\Nsq\Consumer\ActivityPub\Trait;

use App\Entity\Contracts\ActivityPubActivityInterface;
use App\Model\Account;
use App\Model\Attachment;
use App\Model\Follow;
use App\Model\Hashtag;
use App\Model\Instance;
use App\Model\Poll;
use App\Model\Relay;
use App\Model\Status;
use App\Model\StatusesMention;
use App\Nsq\Queue;
use App\Service\Activitypub\ActivitypubService;
use App\Service\AttachmentServiceV3;
use App\Service\DeliveryFailureTracker;
use App\Service\SettingService;
use App\Service\UrisService;
use App\Util\ActivityPub\HttpSignature;
use App\Util\Log;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Hyperf\Coroutine\Exception\ParallelExecutionException;
use Hyperf\Coroutine\Parallel;
use Hyperf\Nsq\Result;
use Hyperf\Guzzle\HandlerStackFactory;
use Jaytaph\TypeArray\TypeArray;
use function Hyperf\Support\env;

use function Hyperf\Support\make;

trait ApRepository
{

    public function getCreateOrPollApData($status, $account, $to, $cc, $inReplyToUri, $tags, $inboxUrl)
    {
        $poll = Poll::where('status_id', $status->id)->first();
        return $poll ? $this->getQuestionApData($status, $account, $to, $cc, $inReplyToUri, $tags, $inboxUrl)
            : $this->getCreateApData($status, $account, $to, $cc, $inReplyToUri, $tags, $inboxUrl);
    }

    public function getCreateApData(Status $status, Account $account, $to, $cc, $inReplyToUri, $tags, $inboxUrl)
    {
        $data = [
            '@context' => [
                ActivityPubActivityInterface::SECURITY_URL,
                ActivityPubActivityInterface::CONTEXT_URL,
            ],
            'id'        => $status->permalink(),
            'type'      => ActivityPubActivityInterface::TYPE_CREATE,
            'actor'     => $account->permalink(),
            'published' => $status->published_at->toIso8601String(),
            'to'        => $to,
            'cc'        => $cc,
            'object'    => [
                'id'           => $status->permalink(),
                'type'         => ActivityPubActivityInterface::TYPE_NOTE,
                'summary'      => null,
                'content'      => $status->content,
                'contentMap'   => ['zh' => $status->content],
                'inReplyTo'    => $inReplyToUri,
                'atomUri'         => $status->permalink(),
                'inReplyToAtomUri'   => $inReplyToUri,
                'published'    => $status->published_at->toAtomString(),
                'url'          => $status->permaurl(),
                'attributedTo' => $account->permalink(),
                'to'           => $to,
                'cc'           => $cc,
                'sensitive'    => (bool) $status->is_sensitive,
                'attachment'   => $this->getAttachments($status, $this->getProxyUrlFunc($inboxUrl)),
                'tag'             => $tags,
                'commentsEnabled' => $status->comments_disabled == 0,
                'capabilities'    => [
                    'announce' => ActivityPubActivityInterface::PUBLIC_URL,
                    'like'     => ActivityPubActivityInterface::PUBLIC_URL,
                    'reply'    => $status->comments_disabled == 1 ? '[]' : ActivityPubActivityInterface::PUBLIC_URL
                ],
            ]
        ];

        if ($inReplyToUri) {
            $data['replies'] = [
                'id' => $status->permalink('replies'),
                'type' => ActivityPubActivityInterface::TYPE_COLLECTION,
                'first' => [
                    'type' => ActivityPubActivityInterface::TYPE_COLLECTION_PAGE,
                    'next' => $status->permalink('replies') . '?page=2',
                    'partOf' => $status->permalink('replies'),
                    'items' => []
                ]
            ];
        }
        return $data;
    }

    public function getQuestionApData(Status $status, Account $account, $to, $cc, $inReplyToUri, $tags, $inboxUrl = null)
    {
        return [
            '@context' => [
                ActivityPubActivityInterface::SECURITY_URL,
                ActivityPubActivityInterface::CONTEXT_URL,
            ],
            'id'        => $status->permalink(),
            'type'      => ActivityPubActivityInterface::TYPE_CREATE,
            'actor'     => $account->permalink(),
            'published' => $status->published_at->toAtomString(),
            'to'        => $to,
            'cc'        => $cc,
            'object'    => [
                'id'              => $status->permalink(),
                'type'            => ActivityPubActivityInterface::TYPE_QUESTION,
                'summary'         => null,
                'content'         => $status->content,
                'inReplyTo'       => $inReplyToUri,
                'atomUri'         => $status->permalink(),
                'inReplyToAtomUri'   => $inReplyToUri,
                'url'             => $status->permaurl(),
                'attributedTo'    => $account->permalink(),
                'published'       => $status->published_at->toAtomString(),
                'to'              => $to,
                'cc'              => $cc,
                'sensitive'       => (bool) $status->is_sensitive,
                'attachment'      => [],
                'tag'             => $tags,
                'commentsEnabled' => $status->comments_disabled == 0,
                'capabilities'    => [
                    'announce' => ActivityPubActivityInterface::PUBLIC_URL,
                    'like'     => ActivityPubActivityInterface::PUBLIC_URL,
                    'reply'    => $status->comments_disabled == true ? '[]' : ActivityPubActivityInterface::PUBLIC_URL
                ],
                'endTime' => $status->polls->expires_at->toAtomString(),
                'oneOf'   => $this->getOneOf($status)
            ],
        ];
    }

    public function getOneOf(Status $status)
    {
        return \Hyperf\Collection\collect($status->polls->poll_options)->map(function ($option, $index) use ($status) {
            return [
                'type'    => ActivityPubActivityInterface::TYPE_NOTE,
                'name'    => $option,
                'replies' => [
                    'type'       => ActivityPubActivityInterface::TYPE_COLLECTION,
                    'totalItems' => $status->polls->cached_tallies[$index]
                ]
            ];
        });
    }



    public function getUpdateApData(Status $status, Account $account, $to, $cc, $inReplyToUri, $tags, $inboxUrl)
    {
        $latestEdit = $status->edits()->latest()->first();
        return [
            '@context' => [
                ActivityPubActivityInterface::SECURITY_URL,
                ActivityPubActivityInterface::CONTEXT_URL,
            ],
            'id'        => $status->permalink('#updates/' . $latestEdit->id),
            'type'      => ActivityPubActivityInterface::TYPE_UPDATE,
            'actor'     => $status->account->permalink(),
            'published' => $latestEdit->created_at->toAtomString(),
            'to'        => $to,
            'cc'        => $cc,
            'object'    => [
                'id'           => $status->permalink(),
                'type'         => ActivityPubActivityInterface::TYPE_NOTE,
                'summary'      => $status->is_sensitive ? $status->spoiler_text : null,
                'content'      => $status->content,
                'inReplyTo'    => $inReplyToUri,
                'published'    => $status->published_at->toAtomString(),
                'url'          => $status->permaurl(),
                'attributedTo' => $account->permalink(),
                'to'           => $to,
                'cc'           => $cc,
                'sensitive'    => (bool) $status->is_sensitive,
                'attachment'   => $this->getAttachments($status, $this->getProxyUrlFunc($inboxUrl)),
                'tag'             => $tags,
                'commentsEnabled' => $status->comments_disabled == 0,
                'updated'         => $latestEdit->created_at->toAtomString(),
                'capabilities'    => [
                    'announce' => ActivityPubActivityInterface::PUBLIC_URL,
                    'like'     => ActivityPubActivityInterface::PUBLIC_URL,
                    'reply'    => $status->comments_disabled == true ? '[]' : ActivityPubActivityInterface::PUBLIC_URL
                ],
            ]
        ];
    }

    public function getDeleteApData(Status $status)
    {
        return [
            '@context' => ActivityPubActivityInterface::CONTEXT_URL,
            'id'                     => $status->permalink('#delete'),
            'type'                     => ActivityPubActivityInterface::TYPE_DELETE,
            'actor'                 => $status->account->permalink(),
            'object'                 => [
                'id'                 => $status->permalink(),
                'type'                 => ActivityPubActivityInterface::TYPE_TOMBSTONE
            ]
        ];
    }

    public function getAnnounceApData(Status $status, Status $parent)
    {
        return [
            '@context'  => ActivityPubActivityInterface::CONTEXT_URL,
            'id'        => $status->permalink(),
            'type'         => ActivityPubActivityInterface::TYPE_ANNOUNCE,
            'actor'        => $status->account->permalink(),
            'to'         => [ActivityPubActivityInterface::PUBLIC_URL],
            'cc'         => [
                $parent->account->permalink(),
                $status->account->permalink('followers')
            ],
            'published' => $status->created_at->format(DATE_ISO8601),
            'object'    => $parent->permalink(),
        ];
    }

    public function send(Status $status, Account $account, callable $apDataFunc, $consumerName): ?string
    {
        Log::info($consumerName . ' start end');
        if (!$this->pushActivitypubSwitchOn($account, $status)) {
            return Result::DROP;
        }

        $targetAccountIds = StatusesMention::where('status_id', $status->id)
            ->where('account_id', $account->id)
            ->where('silent', 0)
            ->pluck('target_account_id');
        $mentionsAudience = [];
        $mentions = Account::findMany($targetAccountIds)
            ->filter(fn($item) => $item->domain != null)
            ->map(function ($item) use (&$mentionsAudience) {
                $mentionsAudience[] = $item->inbox_uri;
                return $item->permalink();
            })->toArray();

        $inReplyToUri = null;
        $parentInbox = [];
        if ($status->reply_to_id && $parent = $status->parent()) {
            $inReplyToUri = $parent->permalink();
            if ($parent->isRemote()) {
                $parentInbox[] = $parent->inbox_uri;
            }
        }

        $to = array_merge([$account->permalink(UrisService::FollowersPath)], $mentions);
        $cc = [];
        if ($status->scope == Status::SCOPE_PUBLIC) {
            $to[] = ActivityPubActivityInterface::PUBLIC_URL;
        } elseif ($status->scope == Status::SCOPE_UNLISTED) {
            $cc[] = ActivityPubActivityInterface::PUBLIC_URL;
        }

        $tags = $this->getTags($status);

        $audience = Follow::where('target_account_id', $account->id)
            ->get()
            ->filter(fn($item) => $item->account?->domain != null)
            ->map(function ($item) {
                return $item->account?->inbox_uri;
            })->toArray();
        $audience = array_values(array_unique(array_merge($audience, $mentionsAudience, $parentInbox)));
        $pendingProcess = [];
        foreach ($audience as $inboxUrl) {
            $url = $inboxUrl;

            //check instance
            $instance = Instance::where('domain', parse_url($url, PHP_URL_HOST))->first();
            if (!empty($instance) && $instance->is_disable_sync) {
                continue;
            }

            $apData = $apDataFunc($status, $account, $to, $cc, $inReplyToUri, $tags, $url);

            $headers = HttpSignature::sign($account, $url, $apData, [
                'Content-Type' => 'application/activity+json; profile="' . ActivityPubActivityInterface::CONTEXT_URL . '"',
                'User-Agent'   => ActivitypubService::getUa(),
            ]);
            $pendingProcess[] = ['url' => $url, 'headers' => $headers, 'body' => $apData];
        }

        \Hyperf\Collection\collect($pendingProcess)->chunk(2)->each(function ($items) {
            Queue::send($items->toArray(), Queue::TOPIC_HTTP_REQUEST);
        });

        Log::info($consumerName . ' final end');
        return Result::ACK;
    }

    public function sendRelay(Status $status, callable $apDataFunc, $consumerName)
    {
        $account = $status->account;
        if (!$this->pushActivitypubSwitchOn($account, $status)) {
            return Result::DROP;
        }

        $inReplyToUri = null;
        if ($status->reply_to_id && $parent = $status->parent()) {
            $inReplyToUri = $parent->permalink();
        }

        $to = [ActivityPubActivityInterface::PUBLIC_URL];
        $cc = [$account->permalink(UrisService::FollowersPath)];
        $tags = $this->getTags($status);

        $msgTag = __FUNCTION__ . '_' . $consumerName;
        $pendingProcess = [];
        Relay::where('state', '=', Relay::STATE_ACCEPTED)
            ->where(function ($q) {
                $q->where('mode', Relay::MODE_WRITE_ONLY)->orWhereNull('mode');
            })
            ->get()
            ->each(function (Relay $relay) use (&$pendingProcess, &$successUrls, $status, $account, $to, $cc, $tags, $apDataFunc, $msgTag, $inReplyToUri) {
                $url = $relay->inbox_url;
                $apData = $apDataFunc($status, $account, $to, $cc, $inReplyToUri, $tags, $url);

                // todo relay sign --- start

                // sign for php
                $signatureData = [
                    'type' => 'RsaSignature2017',
                    'creator' => $account->permalink('#main-key'),
                    'created' => Carbon::now()->toIso8601String(),
                ];
                $cpSignData = $signatureData;
                $cpApData = $apData;
                unset($cpSignData['type']);
                unset($cpApData['signature']);
                $cpSignData = array_merge($cpSignData, ['@context' => 'https://w3id.org/identity/v1']);
                $options_hash = $this->hash($cpSignData);
                $document_hash = $this->hash($cpApData);
                $to_be_verified = $options_hash . $document_hash;
                $key = openssl_pkey_get_private($account->private_key);
                openssl_sign($to_be_verified, $signature, $key, OPENSSL_ALGO_SHA256);
                $signature = base64_encode($signature);
                $signatureData['signatureValue'] = $signature;
                $apData['signature'] = $signatureData;

                //                $res = $client->post('http://localhost:3081', [
                //                    'json' => $apData,
                //                ]);
                //                var_dump($res->getBody()->getContents());
                //                return;
                // todo relay sign --- end

                $headers = HttpSignature::sign($account, $url, $apData, [
                    'Content-Type' => 'application/activity+json; profile="' . ActivityPubActivityInterface::CONTEXT_URL . '"',
                    'User-Agent'   => ActivitypubService::getUa(),
                ]);
                $pendingProcess[] = ['url' => $url, 'headers' => $headers, 'body' => $apData];
            });

        \Hyperf\Collection\collect($pendingProcess)->chunk(2)->each(function ($items) {
            Queue::send($items->toArray(), Queue::TOPIC_HTTP_REQUEST);
        });
        Log::info($msgTag . ' final end');
        return Result::ACK;
    }

    public function requestSuccessUrls($urls)
    {
        foreach ($urls as $url) {
            make(DeliveryFailureTracker::class, ['urlOrHost' => $url])->trackSuccess();
        }
    }

    public function requestFailThrowAbles(ParallelExecutionException $e)
    {
        foreach ($e->getThrowables() as $throwable) {
            make(DeliveryFailureTracker::class, ['urlOrHost' => $throwable->getRequest()->getUri()->getHost()])->trackFailure();
        }
    }

    public function getTags(Status $status)
    {
        $mentionsTag = $status->mentions->map(function (Account $mention) {
            return [
                'type' => ActivityPubActivityInterface::TYPE_MENTION,
                'href' => $mention->permalink(),
                'name' => '@' . $mention->acct
            ];
        })->toArray();

        if ($status->reply_to_id && $parent = $status->parent()) {
            $parentAccount = $parent->account;
            $webfinger = $parentAccount->acct;
            $name = str_starts_with($webfinger, '@') ?
                $webfinger :
                '@' . $webfinger;
            $reply = [
                'type' => ActivityPubActivityInterface::TYPE_MENTION,
                'href' => $parentAccount->permalink(),
                'name' => $name
            ];
            $mentionsTag = array_merge($reply, $mentionsTag);
        }
        $hashtags = $status->hashtags->map(function (Hashtag $hashtag) {
            return [
                'type' => ActivityPubActivityInterface::TYPE_HASHTAG,
                'href' => $hashtag->href ?: getApHostUrl() . '/explore/hashtag/' . urlencode($hashtag->name),
                'name' => "#{$hashtag->name}",
            ];
        })->toArray();

        $emojis = \Hyperf\Collection\collect($status->emoji)->map(function ($emoji) {
            $url = $emoji['image_url'];
            $mediaType = str_ends_with($url, '.png') ? 'image/png' : 'image/jpg';
            return [
                'id'      => getApHostUrl() . '/emojis/' . $emoji['id'],
                'type'    => ActivityPubActivityInterface::TYPE_EMOJI,
                'name'    => $emoji['shortcode'],
                'updated' => Carbon::parse($emoji['image_updated_at'])->toAtomString(),
                'icon'    => [
                    'type'      => ActivityPubActivityInterface::TYPE_IMAGE,
                    'mediaType' => $mediaType,
                    'url'       => $url
                ]
            ];
        })->toArray();

        return array_merge($mentionsTag, $hashtags, $emojis);
    }

    public function toProxyUrl($inboxUrl, $url, $remoteUrl)
    {
        $instance = Instance::where('domain', parse_url($inboxUrl, PHP_URL_HOST))->first();
        if ($instance && $instance->is_proxy && $remoteUrl) {
            return getApHostUrl() . '/proxy?url=' . $remoteUrl;
        }
        return $url;
    }

    public function getAttachments(Status $status, callable $proxyFunc)
    {
        $mediaFunc = function (Attachment $media) use ($proxyFunc) {
            return [
                'type'      => $media->type,
                'mediaType' => $media->media_type,
                'url'       => $proxyFunc($media->url, $media->remote_url),
                'name'      => $media->name,
                'width'     => $media->width,
                'height'    => $media->height,
                'blurhash'  => $media->blurhash,
                'file_type' => $media->file_type,
                'thumbnail_url' => $proxyFunc($media->thumbnail_url, $media->thumbnail_url),
                'thumbnail_height' => $media->thumbnail_height,
                'thumbnail_width' => $media->thumbnail_width,
                'thumbnail_file_size' => $media->thumbnail_file_size,
                'file_size' => $media->file_size,
            ];
        };

        if ($status->fee > 0 && $status->attachments->isNotEmpty()) {
            $syncAttachmentId = env('SYNC_ATTACHMENT_ID');
            if (empty($syncAttachmentId)) {
                return [];
            }
            return [$mediaFunc(Attachment::findOrFail($syncAttachmentId))];
        }

        return $status->attachments()->get()->map($mediaFunc)->toArray();
    }

    public function getProxyUrlFunc($inboxUrl)
    {
        return function ($url, $remoteUrl) use ($inboxUrl) {
            return $this->toProxyUrl($inboxUrl, $url, $remoteUrl);
        };
    }

    protected function canonicalize(TypeArray $data): string
    {
        try {
            $ret = jsonld_normalize(
                $this->array2object($data),
                [
                    'algorithm' => 'URDNA2015',
                    'format' => 'application/nquads',
                ]
            );
        } catch (\Throwable $e) {
            throw new \RuntimeException('Cannot canonicalize message', 0, $e);
        }

        return strval($ret);
    }

    protected function hash($data): string
    {
        return hash('sha256', $this->canonicalize(new TypeArray($data)));
    }

    protected function array2object(TypeArray $data): mixed
    {
        $json = json_encode($data->toArray());
        if (!$json) {
            $json = '';
        }

        return json_decode($json, false, 512, JSON_THROW_ON_ERROR);
    }

    protected function pushActivitypubSwitchOn(Account $account, Status $status): bool
    {
        if (!$account->enable_activitypub || !$status->enable_activitypub) {
            return false;
        }

        if ($status->is_sensitive && !SettingService::push_local_sensitive()) {
            return false;
        }

        return true;
    }

    protected function statusPaidContentPad(Status $status)
    {
        if ($status->fee > 0) {
            $status->content .= '<br/><p>付费内容，请到源地址解锁查看 <a href="' . $status->permaurl() . '">' . $status->permaurl() . '</a></p>';
        }
    }
}
