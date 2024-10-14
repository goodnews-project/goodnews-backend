<?php

namespace App\Util\ActivityPub;

use Hyperf\Collection\Arr;
use Hyperf\DbConnection\Db;
use Hyperf\Stringable\Str;
use Hyperf\Validation\Contract\ValidatorFactoryInterface;
use Hyperf\Validation\Rule;
use App\{Entity\Contracts\ActivityPubActivityInterface,
    Exception\InboxException,
    Model\Account,
    Model\Attachment,
    Model\CustomEmoji,
    Model\Hashtag,
    Model\Instance,
    Model\Notification,
    Model\Poll,
    Model\Status,
    Model\StatusesMention,
    Model\StatusHashtag,
    Model\WalletAddressLog,
    Nsq\Queue,
    Resource\Mastodon\StatusResource,
    Service\Activitypub\ActivitypubService,
    Service\AttachmentService,
    Service\AttachmentServiceV2,
    Service\AttachmentServiceV3,
    Service\RedisService,
    Service\WebfingerService,
    Service\Websocket,
    Util\Lexer\Extractor,
    Util\Log};
use App\Aspect\Annotation\ExecTimeLogger;
use Carbon\Carbon;
use GuzzleHttp\Client;
use function Hyperf\Support\env;
use function Hyperf\Support\make;

class Helper {

    protected static ValidatorFactoryInterface $validationFactory;

    public static function setValidationFactory(ValidatorFactoryInterface $validationFactory)
    {
        self::$validationFactory = $validationFactory;
    }

    public static function validateObject($data)
    {
        $verbs = ['Create', 'Announce', 'Like', 'Follow', 'Delete', 'Accept', 'Reject', 'Undo', 'Tombstone'];

        $valid = self::$validationFactory->make($data, [
            'type' => [
                'required',
                'string',
                Rule::in($verbs)
            ],
            'id' => 'required|string',
            'actor' => 'required|string|url',
            'object' => 'required',
            'object.type' => 'required_if:type,Create',
            'object.attributedTo' => 'required_if:type,Create|url',
            'published' => 'required_if:type,Create|date'
        ])->passes();

        return $valid;
    }

    public static function verifyAttachments($data)
    {
        if(!isset($data['object']) || empty($data['object'])) {
            $data = ['object'=>$data];
        }

        $activity = $data['object'];

        $mimeTypes = ['video/mp4', 'image/jpeg', 'image/png'];
        $mediaTypes = in_array('video/mp4', $mimeTypes) ? ['Document', 'video', 'Image'] : ['Document', 'Image'];

        if(empty($activity['attachment'])) {
            return false;
        }

        $attachment = $activity['attachment'];

        $valid = self::$validationFactory->make($attachment, [
            '*.type' => [
                'required',
                'string',
                Rule::in($mediaTypes)
            ],
            '*.url' => 'required',
            '*.mediaType'  => [
                'required',
                'string',
                Rule::in($mimeTypes)
            ],
            '*.name' => 'sometimes|nullable|string'
        ])->passes();

        return $valid;
    }

    public static function normalizeAudience($data, $localOnly = true)
    {
        if(!isset($data['to'])) {
            return;
        }

        $audience = [];
        $audience['to'] = [];
        $audience['cc'] = [];
        $scope = 'private';

        if(is_array($data['to']) && !empty($data['to'])) {
            foreach ($data['to'] as $to) {
                if($to == 'https://www.w3.org/ns/activitystreams#Public') {
                    $scope = 'public';
                    continue;
                }
                $url = $localOnly ? self::validateLocalUrl($to) : self::validateUrl($to);
                if($url != false) {
                    array_push($audience['to'], $url);
                }
            }
        }

        if(is_array($data['cc']) && !empty($data['cc'])) {
            foreach ($data['cc'] as $cc) {
                if($cc == 'https://www.w3.org/ns/activitystreams#Public') {
                    $scope = 'unlisted';
                    continue;
                }
                $url = $localOnly ? self::validateLocalUrl($cc) : self::validateUrl($cc);
                if($url != false) {
                    array_push($audience['cc'], $url);
                }
            }
        }
        $audience['scope'] = $scope;
        return $audience;
    }

    public static function userInAudience($profile, $data)
    {
        $audience = self::normalizeAudience($data);
        $url = $profile->permalink();
        return in_array($url, $audience['to']) || in_array($url, $audience['cc']);
    }

    public static function validateUrl($url)
    {
        if(is_array($url)) {
            $url = $url[0];
        }

        $localhosts = [
            '127.0.0.1', 'localhost', '::1'
        ];

        if(strtolower(mb_substr($url, 0, 8)) !== 'https://') {
            return false;
        }

        if(substr_count($url, '://') !== 1) {
            return false;
        }

        if(mb_substr($url, 0, 8) !== 'https://') {
            $url = 'https://' . substr($url, 8);
        }

        $host = parse_url($url, PHP_URL_HOST);

        if(in_array($host, $localhosts)) {
            return false;
        }

        return $url;
    }

    public static function validateLocalUrl($url)
    {
        $url = self::validateUrl($url);
        if (!$url) {
            return false;
        }

        $domain = env('AP_HOST');
        $host = parse_url($url, PHP_URL_HOST);
        return strtolower($domain) === strtolower($host) ? $url : false;
    }

    public static function zttpUserAgent()
    {
        $version = env('VERSION', '2.0');
        $url = getApHostUrl();
        return [
            'Accept'     => 'application/activity+json',
            'User-Agent' => ActivitypubService::getUa(),
        ];
    }

    public static function fetchFromUrl($url = '')
    {
        if(self::validateUrl($url) == false) {
            return false;
        }

        $res = ActivitypubService::get($url);
        if(empty($res)) {
            return false;
        }

        $res = json_decode($res, true);
        if(json_last_error() == JSON_ERROR_NONE) {
            return $res;
        } else {
            return false;
        }
    }

    public static function fetchAccountFromUrl($url)
    {
        return self::fetchFromUrl($url);
    }

    public static function pluckval($val)
    {
        if(is_string($val)) {
            return $val;
        }

        if(is_array($val)) {
            return !empty($val) ? $val[0] : null;
        }

        return null;
    }

    public static function statusFirstOrFetch($url, $replyTo = false)
    {
        $redis = \Hyperf\Support\make(RedisService::class);
        $key = md5($url);
        if (!$redis->acquireLock($key)) {
            throw new InboxException('cannot get lock to fetch status', compact('url'));
        }

        try {
            $status = self::statusFirstFetch($url, $replyTo);
            $redis->releaseLock($key);
            return $status;
        } catch (\Exception $e) {
            $s = 'statusFirstOrFetch exception:'.$e->getMessage().', file:'.$e->getFile().':'.$e->getLine().' , url:'.$url.PHP_EOL;
            Log::error('statusFirstOrFetch exception:'.$e->getMessage().', file:'.$e->getFile().':'.$e->getLine().' , url:'.$url);
            var_dump($s.$e->getTraceAsString());
            $redis->releaseLock($key);
        }
        return null;
    }

    public static function statusFirstFetch($url, $replyTo = false)
    {
        $url = self::validateUrl($url);
        if($url == false) {
            return null;
        }

        if (self::validateLocalUrl($url)) {
            $id = Arr::last(explode('/', $url));
            return Status::findOrFail($id);
        }

        $cached = Status::where('uri', $url)->orWhere('url', $url)->first();
        Log::info('statusFirstOrFetch-cached', ['cached' => $cached != null, 'url' => $url]);
        if($cached) {
            Log::info('statusFirstOrFetch-cached return');
            return $cached;
        }
        Log::info('statusFirstOrFetch-cached pass');

        $res = self::fetchFromUrl($url);
        if(empty($res) || isset($res['error']) || !isset($res['@context']) || !isset($res['published']) ) {
            return null;
        }

        if(isset($res['object'])) {
            $activity = $res;
        } else {
            $activity = ['object' => $res];
        }

        $id = isset($res['id']) ? self::pluckval($res['id']) : self::pluckval($url);
        $idDomain = parse_url($id, PHP_URL_HOST);
        $urlDomain = parse_url($url, PHP_URL_HOST);

        if(!self::validateUrl($id)) {
            return null;
        }

        if(!isset($activity['object']['attributedTo'])) {
            return null;
        }

        $attributedTo = is_string($activity['object']['attributedTo']) ?
            $activity['object']['attributedTo'] :
            (is_array($activity['object']['attributedTo']) ?
                \Hyperf\Collection\collect($activity['object']['attributedTo'])
                    ->filter(function($o) {
                        return $o && isset($o['type']) && $o['type'] == ActivityPubActivityInterface::TYPE_PERSON;
                    })
                    ->pluck('id')
                    ->first() : null
            );

        if($attributedTo) {
            $actorDomain = parse_url($attributedTo, PHP_URL_HOST);
            if(!self::validateUrl($attributedTo) ||
                $idDomain !== $actorDomain ||
                $actorDomain !== $urlDomain
            )
            {
                return null;
            }
        }

        if($idDomain !== $urlDomain) {
            return null;
        }

        $account = self::accountFirstOrNew($attributedTo);
        if (!$account) {
            return null;
        }

        if($res['type'] === ActivityPubActivityInterface::TYPE_QUESTION) {
            $status = self::storePoll(
                $account,
                $res
            );
        } else {
            $status = self::storeStatus($account, $res);
        }
        return $status;
    }

    public static function storePoll(Account $account, $activity)
    {
        if(!isset($activity['endTime']) || !isset($activity['oneOf']) || !is_array($activity['oneOf']) || count($activity['oneOf']) > 4) {
            return null;
        }

        $id = isset($activity['id']) ? self::pluckval($activity['id']) : self::pluckval($activity['url']);
        $url = isset($activity['url']) && is_string($activity['url']) ? self::pluckval($activity['url']) : self::pluckval($id);

        $ts = self::pluckval($activity['published']);
        $scope = self::getScope($activity, $url);

        $is_sensitive = self::getSensitive($activity, $url);

        $options = \Hyperf\Collection\collect($activity['oneOf'])->map(function($option) {
            return $option['name'];
        })->toArray();

        $cachedTallies = \Hyperf\Collection\collect($activity['oneOf'])->map(function($option) {
            return $option['replies']['totalItems'] ?? 0;
        })->toArray();

        $status = new Status;
        $status->account_id = $account->id;
        $status->url = $url;
        $status->uri = $id;
        $status->content = handleStatusContent($activity['content']);
        $status->published_at = Carbon::parse($ts)->toDatetimeString();
        $status->is_local = false;
        $status->is_sensitive = $is_sensitive;
        $status->scope = $scope;
        $status->save();

        $poll = new Poll;
        $poll->status_id = $status->id;
        $poll->account_id = $status->account_id;
        $poll->poll_options = $options;
        $poll->cached_tallies = $cachedTallies;
        $poll->votes_count = array_sum($cachedTallies);
        $poll->expires_at = Carbon::now()->parse($activity['endTime'])->toDatetimeString();
        $poll->last_fetched_at = Carbon::now();
        $poll->save();

        Websocket::pushPublicRemote(StatusResource::make($status));
        Websocket::pushStatusToFollower($status);
        return $status;
    }

    #[ExecTimeLogger("inbox", 'inbox')]
    public static function storeStatus(Account $account, $activity)
    {
        $time_start = microtime(true); 
        $id = isset($activity['id']) ? self::pluckval($activity['id']) : self::pluckval($activity['url']);
        $url = isset($activity['url']) && is_string($activity['url']) ? self::pluckval($activity['url']) : self::pluckval($id);
        if(!self::validateUrl($id) || !self::validateUrl($url)) {
            Log::warning('not validate url', compact('id', 'url'));
            return null;
        }

        $reply_to = self::getReplyTo($activity);
        $scope = self::getScope($activity, $url);
        $is_sensitive = self::getSensitive($activity, $url);

        $ts = self::pluckval($activity['published']);

        $status = Status::updateOrCreate(
            [
                'uri' => $id
            ], [
                'account_id' => $account->id,
                'url' => $url,
                'published_at' => Carbon::parse($ts)->toDatetimeString(),
                'reply_to_id' => $reply_to,
                'is_local' => $account->isLocal(),
                'is_sensitive' => $is_sensitive,
                'content' => handleStatusContent($activity['content']),
                'scope' => $scope,
            ]
        );

        $entities = Extractor::create()->extract($status->content);
        if(!empty($entities['urls']) && empty($activity['attachment'])){
            Queue::send(
                array_merge($entities,['status_id'=>$status->id]),
                Queue::TOPIC_STATUS_HAS_LINKS
            );
        }

        if ($reply_to == null) {
            self::importNoteAttachment($activity, $status);
        } else {
            if(isset($activity['attachment']) && !empty($activity['attachment'])) {
                self::importNoteAttachment($activity, $status);
            }
            self::statusReply($status);
        }

        if(isset($activity['tag']) && is_array($activity['tag']) && !empty($activity['tag'])) {
            self::statusTags($activity, $status);
        }

        Log::info('storeStatus success,id:'.$status->id, $activity);
        $account->status_count += 1;
        $account->save();
        $execution_time =microtime(true) - $time_start;

        Websocket::pushPublicRemote(StatusResource::make($status));
        Websocket::pushStatusToFollower($status);
        Log::info("inbox : store status $execution_time");
        return $status;
    }

    #[ExecTimeLogger('inbox', 'inbox')]
    public static function importNoteAttachment($data, Status $status)
    {
        $attachments = isset($data['object']) ? ($data['object']['attachment'] ?? []) : ($data['attachment'] ?? []);
        if (empty($attachments)) {
            return;
        }
        $instance = Instance::where('domain', $status->account->domain)->first();

        foreach($attachments as $key => $media) {
            if (empty($media['url'])) {
                continue;
            }

            $url = $media['url'];

            $name = isset($media['name']) ? strip_tags($media['name']) : null;
            $attachment = new Attachment();

            $attachment->tid = $status->id;
            $attachment->from_table = $status::class;
            $attachment->file_type = array_key_exists($media['mediaType'], AttachmentServiceV3::VIDEOS) ? Attachment::FILE_TYPE_VIDEO : Attachment::FILE_TYPE_IMAGE;
            $attachment->remote_url = $url;
            $attachment->name = $name;
            $attachment->type = $media['type'] ?? null;
            $attachment->media_type = $media['mediaType'];
            $attachment->blurhash = $media['blurhash'] ?? null;
            $attachment->width = $media['width'] ?? null;
            $attachment->height = $media['height'] ?? null;
            $attachment->status = Attachment::STATUS_WAIT;

            if ((isLocalAp($url) && !empty($media['file_type'])) || 
                    ( $instance && $instance->is_disable_download )
            ) {
                // not need to download
                $attachment->url = $url;
                $attachment->thumbnail_url = $media['thumbnail_url'] ?? null;
                $attachment->thumbnail_width = $media['thumbnail_width'] ?? null;
                $attachment->thumbnail_height = $media['thumbnail_height'] ?? null;
                $attachment->status = Attachment::STATUS_FINISH;
                $attachment->save();
            } else {
                $attachment->save();
                \Hyperf\Support\make(AttachmentServiceV3::class)->attachmentDownload($attachment->id);
            }

        }
    }

    public static function statusTags($activity, Status $status)
    {
        $res = $activity;

        if(isset($res['tag']['type'], $res['tag']['name'])) {
            $res['tag'] = [$res['tag']];
        }

        $tags = \Hyperf\Collection\collect($res['tag']);

        // Emoji
        $emojiTags = $tags->filter(function($tag) {
            return $tag && isset($tag['id'], $tag['icon'], $tag['name'], $tag['type']) && $tag['type'] == ActivityPubActivityInterface::TYPE_EMOJI;
        });
        if ($emojiTags->isNotEmpty()) {
            self::storeEmoji($emojiTags->toArray());
        }

        // Hashtags
        $tags->filter(function($tag) {
            return $tag && $tag['type'] == ActivityPubActivityInterface::TYPE_HASHTAG && isset($tag['href'], $tag['name']);
        })->map(function($tag) use($status) {
            $name = str_starts_with($tag['name'], '#') ?
                substr($tag['name'], 1) : $tag['name'];

            $hashtag = Hashtag::updateOrCreate([
                'name' => $name,
            ], ['slug' => Str::slug($name, '-', null), 'href' => $tag['href']]);

            StatusHashtag::firstOrCreate([
                'status_id' => $status->id,
                'hashtag_id' => $hashtag->id,
                'account_id' => $status->account_id,
            ]);
        });

        // Mentions
        $tags->filter(function($tag) {
            return $tag &&
                $tag['type'] == ActivityPubActivityInterface::TYPE_MENTION &&
                isset($tag['href']) &&
                str_starts_with($tag['href'], 'https://');
        })
            ->map(function($tag) use($status) {
                if(self::validateLocalUrl($tag['href'])) {
                    $parts = explode('/', $tag['href']);
                    if(!$parts) {
                        return;
                    }
                    $account_id = Account::where('username', end($parts))->value('id');
                } else {
                    $acct = self::accountFetch($tag['href']);
                    if(!$acct) {
                        return;
                    }
                    $account_id = $acct->id;
                }
                $mention = new StatusesMention();
                $mention->status_id = $status->id;
                $mention->account_id = $status->account_id;
                $mention->target_account_id = $account_id;
                $mention->href = $tag['href'];
                $mention->name = $tag['name'];
                $mention->save();

                self::notifyMention($status, $mention);
            });
    }

    public static function notifyMention(Status $status, StatusesMention $mention)
    {
        $account = $status->account;
        $mentionAccountId = $mention->account_id;

        $exists = Notification::where('account_id', $account->id)
                  ->where('target_account_id', $mentionAccountId)
                  ->where('status_id', $status->id)
                  ->where('notify_type', Notification::NOTIFY_TYPE_MENTION)
                  ->where('read', 0)
                  ->exists();

        if ($account->id === $mentionAccountId || $exists) {
            Log::info('notifyMention: account->id === mentionAccountId || exists, status_id:'.$status->id);
            return;
        }

        Notification::firstOrCreate(
            [
                'account_id' => $account->id,
                'target_account_id' => $mentionAccountId,
                'notify_type' => Notification::NOTIFY_TYPE_MENTION,
                'status_id' => $status->id,
            ]
        );
    }

    public static function getReplyTo($activity)
    {
        $reply_to = null;
        $inReplyTo = isset($activity['inReplyTo']) && !empty($activity['inReplyTo']) ?
            self::pluckval($activity['inReplyTo']) :
            false;

        if($inReplyTo) {
            $reply_to = self::statusFirstOrFetch($inReplyTo);
            if($reply_to) {
                $reply_to = \Hyperf\Support\optional($reply_to)->id;
            }
        } else {
            $reply_to = null;
        }

        return $reply_to;
    }

    public static function statusReply(Status $status)
    {
        $account = $status->account;
        $reply = Status::find($status->reply_to_id);
        if (!$account || !$reply) {
            Log::info('!account || !reply, status:'.$status->id);
            return;
        }

        $replyToAccount = $reply->account;
        $exists = Notification::where('account_id', $account->id)
            ->where('target_account_id', $replyToAccount->id)
            ->where('notify_type', Notification::NOTIFY_TYPE_MENTION)
            ->where('status_id', $status->id)
            ->where('read',0)
            ->exists();
        if ($exists) {
            Log::info('exists, status:'.$status->id);
            return;
        }

        $reply->reply_count = $reply->reply_count + 1;
        $reply->save();

        $status->reply_to_account_id = $replyToAccount->id;
        $status->save();

        $notification = new Notification();
        $notification->account_id = $account->id;
        $notification->target_account_id = $replyToAccount->id;
        $notification->notify_type = Notification::NOTIFY_TYPE_MENTION;
        $notification->status_id = $status->id;
        $notification->save();
    }

    public static function getSensitive($activity, $url)
    {
        $id = isset($activity['id']) ? self::pluckval($activity['id']) : self::pluckval($url);
        $url = isset($activity['url']) ? self::pluckval($activity['url']) : $id;
        $urlDomain = parse_url($url, PHP_URL_HOST);

        $cw = isset($activity['sensitive']) ? (bool) $activity['sensitive'] : false;

        $sensitiveDomains = explode(',', env('SENSITIVE_DOMAINS', ''));
        if(in_array($urlDomain, $sensitiveDomains)) {
            $cw = true;
        }

        return $cw;
    }
    #[ExecTimeLogger("inbox", 'inbox')]
    public static function accountFirstOrNew($url)
    {
        $url = self::validateUrl($url);
        if($url == false) {
            return null;
        }

        $host = parse_url($url, PHP_URL_HOST);
        if(env('AP_HOST') == $host) {
            $username = Arr::last(explode('/', $url));
            return Account::where('username', $username)->whereNull('domain')->firstOrFail();
        }

        try {
            $account = Account::withTrashed()->where('uri', $url)->firstOrFail();
        } catch (\Exception $e) {
            $account = self::accountUpdateOrCreate($url);
        }

        if (!$account) {
            Log::warning('account is null, account:'.$account.', url:'.$url);
            return null;
        }

        if($account->isLocal()) {
            return $account;
        }

        if(empty($account->last_webfingered_at) || $account->last_webfingered_at->lt(Carbon::now()->subHours(24))) {
            return self::accountUpdateOrCreate($url);
        }
        return $account;
    }

    public static function accountUpdateOrCreate($url)
    {
        $res = self::fetchAccountFromUrl($url);
        if(!$res || isset($res['id']) == false) {
            return null;
        }

        $domain = parse_url($res['id'], PHP_URL_HOST);
        if(!isset($res['preferredUsername']) && !isset($res['nickname'])) {
            Log::info('accountUpdateOrCreate-preferredUsername is null');
            return null;
        }
        $username = (string) ($res['preferredUsername'] ?? $res['nickname']);
        if(empty($username)) {
            Log::info('accountUpdateOrCreate-username is null');
            return null;
        }

        $acct = "{$username}@{$domain}";

        if(!self::validateUrl($res['inbox'])) {
            Log::info('accountUpdateOrCreate-res[inbox] is null,url:'.$res['inbox']);
            return null;
        }

        if(!self::validateUrl($res['id'])) {
            Log::info('accountUpdateOrCreate-res[id] is null,url:'.$res['id']);
            return null;
        }

        Instance::updateOrCreate(['domain' => $domain]);

        try {
            $accountData = [
                'uri' => $res['id'],
                'url' => $res['url'] ?? null,
                'username' => $username,
                'domain' => $domain,
                'display_name' => $res['name'] ?? null,
                'note' => $res['summary'] ?? null,
                'shared_inbox_uri' => isset($res['endpoints']) && isset($res['endpoints']['sharedInbox']) ? $res['endpoints']['sharedInbox'] : null,
                'inbox_uri' => $res['inbox'] ?? null,
                'outbox_uri' => $res['outbox'] ?? null,
                'public_key_uri' => $res['publicKey']['id'],
                'following_uri' => $res['following'] ?? null,
                'followers_uri' => $res['followers'] ?? null,
                'actor_type' => Account::actorTypeMap[$res['type']] ?? 0,
                'is_activate' => 1,
                'manually_approves_follower' => $res['manuallyApprovesFollowers'] ?? 0,
                'last_webfingered_at' => Carbon::now()
            ];

            if (!empty($res['suspended'])) {
                $accountData['suspended_at'] = !empty($res['published']) ? Carbon::parse($res['published'])->toDatetimeString() : Carbon::now();
            }

            $accountData['following_count'] = self::getFollowingCount($accountData['following_uri']);
            $accountData['followers_count'] = self::getFollowersCount($accountData['followers_uri']);

            if (!empty($res['publicKey']['publicKeyPem'])) {
                $accountData['public_key'] = $res['publicKey']['publicKeyPem'];
            }
            if (!empty($res['publicKey']['icon']['url']) || !empty($res['icon']['url'])) {
                $accountData['avatar_remote_url'] = $res['publicKey']['icon']['url'] ?? $res['icon']['url'];
                try {
                    $accountData['avatar'] = \Hyperf\Support\make(AttachmentServiceV3::class)->donwloadAndUpload($accountData['avatar_remote_url']);
                } catch (\Exception $e) {
                    Log::error('avatar download fail:'.$e->getMessage());
                }

            }

            if (!empty($res['publicKey']['image']['url']) || !empty($res['image']['url'])) {
                $accountData['profile_remote_image'] = $res['publicKey']['image']['url'] ?? $res['image']['url'];
                try {
                    $accountData['profile_image'] = \Hyperf\Support\make(AttachmentServiceV3::class)->donwloadAndUpload($accountData['profile_remote_image']);
                } catch (\Exception $e) {
                    Log::error('profile_image download fail:'.$e->getMessage());
                }
            }

            if (!empty($res['publicKey']['tag']) || !empty($res['tag'])) {
                self::storeEmoji($res['publicKey']['tag'] ?? $res['tag']);
            }

            if (!empty($res['publicKey']['attachment']) || !empty($res['attachment'])) {
                $accountData['fields'] = $res['publicKey']['attachment'] ?? $res['attachment'];
            }

            if (!empty($res['extra']['wallet_address'])) {
                $accountData['wallet_address'] = $res['extra']['wallet_address'];
            }

            $account = Account::withTrashed()->where('acct', $acct)->first();
            if ($account) {
                $account = self::updateAccountData($account, $accountData);
            } else {
                $accountData['acct'] = $acct;
                $account = Account::create($accountData);
            }
            Log::info('accountUpdateOrCreate-accountData:', $accountData);
        } catch (\Exception $e) {
            Log::info('accountUpdateOrCreate-exception:'.$e->getMessage().$e->getFile().$e->getLine());
            return null;
        }

        return $account;
    }

    public static function updateAccountData(Account $account, $accountData)
    {
        $account->fill($accountData)->save();
        return $account;
    }

    public static function storeEmoji($tags)
    {
        foreach((array) $tags as $tag) {
            if ($tag['type'] != ActivityPubActivityInterface::TYPE_EMOJI
                || empty($tag['icon']['url'])
                || $tag['icon']['type'] != 'Image'
                || !in_array($tag['icon']['mediaType'], ['image/png', 'image/jpeg', 'image/jpg'])
            ) {
                continue;
            }

            $domain = parse_url($tag['id'], PHP_URL_HOST);

            try {
                $image_url = \Hyperf\Support\make(AttachmentServiceV3::class)->donwloadAndUpload($tag['icon']['url']);
                Log::info("pin pin 3");
            } catch (\Exception $e) {
                Log::error('CustomEmoji::updateOrCreate image_url download fail:'.$e->getMessage());
                return;
            }

            CustomEmoji::updateOrCreate(['domain' => $domain, 'shortcode' => trim($tag['name'], ':')], [
                'uri' => $tag['id'],
                'image_url' => $image_url,
                'image_remote_url' => $tag['icon']['url'],
                'image_updated_at' => Carbon::parse($tag['updated'])->toDateTimeString(),
            ]);
        }
    }

    public static function getFollowingCount($followingUri)
    {
        if (empty($followingUri)) {
            return 0;
        }
        $r = ActivitypubService::get($followingUri);
        if (empty($r)) {
            return 0;
        }
        $count =  json_decode($r, true)['totalItems'] ?? 0;
        $count = max($count,0);
        return min($count,9999999999);
    }

    public static function getFollowersCount($followersUri)
    {
        if (empty($followersUri)) {
            return 0;
        }
        $r = ActivitypubService::get($followersUri);
        if (empty($r)) {
            return 0;
        }
        $count = json_decode($r, true)['totalItems'] ?? 0;
        $count = max($count,0);
        return min($count, 9999999999);
    }

    public static function accountFetch($url)
    {
        return self::accountFirstOrNew($url);
    }

    public static function getScope($activity, $url)
    {
        $id = isset($activity['id']) ? self::pluckval($activity['id']) : self::pluckval($url);
        $url = isset($activity['url']) ? self::pluckval($activity['url']) : self::pluckval($id);
//        $urlDomain = parse_url(self::pluckval($url), PHP_URL_HOST);
        $scope = Status::SCOPE_PRIVATE;

        if(isset($activity['to'])) {
            if(is_array($activity['to']) && in_array(ActivityPubActivityInterface::PUBLIC_URL, $activity['to'])) {
                $scope = Status::SCOPE_PUBLIC;
            }
            if(is_string($activity['to']) && ActivityPubActivityInterface::PUBLIC_URL == $activity['to']) {
                $scope = Status::SCOPE_PUBLIC;
            }
        }

        if(isset($activity['cc'])) {
            if(is_array($activity['cc']) && in_array(ActivityPubActivityInterface::PUBLIC_URL, $activity['cc'])) {
                $scope = Status::SCOPE_UNLISTED;
            }
            if(is_string($activity['cc']) && ActivityPubActivityInterface::PUBLIC_URL == $activity['cc']) {
                $scope = Status::SCOPE_UNLISTED;
            }
        }

        return $scope;
    }

    public static function sendSignedObject($account, $url, $body)
    {
        Log::info('sendSignedObject start', compact('url', 'body'));
        $headers = HttpSignature::sign($account, $url, $body, [
            'Content-Type'	=> 'application/activity+json; profile="https://www.w3.org/ns/activitystreams"',
            'User-Agent'	=> ActivitypubService::getUa(),
        ]);

        try {
            $client = make(Client::class, [
                'timeout' => 15
            ]);
            $res = $client->post($url, [
                'headers' => $headers,
                'json' => $body
            ]);

            Log::info('sendSignedObject---res:', ['reqBody' => $body, 'respBody' => $res->getBody()->getContents(), 'url' => $url]);
        } catch (\Exception $e) {
            Log::error('sendSignedObject exception: '.$e->getMessage(), compact('account', 'url', 'body'));
        }

    }

    public static function getExistsMention($screen_name, $mentions)
    {
        $screen_name = trim($screen_name, '@');
        return \Hyperf\Collection\collect($mentions)->first(function ($mention) use ($screen_name) {
            return $mention['username'] == $screen_name || $mention['acct'] == $screen_name;
        });
    }
}
