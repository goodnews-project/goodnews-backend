<?php

namespace App\Service;

use App\Entity\Contracts\ActivityPubActivityInterface;
use App\Exception\InboxException;
use App\Model\Account;
use App\Model\CustomEmoji;
use App\Model\Follow;
use App\Model\Instance;
use App\Model\Notification;
use App\Model\Relay;
use App\Model\Report;
use App\Model\Status;
use App\Model\StatusesFave;
use App\Nsq\Queue;
use App\Request\FollowRequest;
use App\Service\Activitypub\ActivitypubService;
use App\Util\ActivityPub\HttpSignature;
use App\Util\Log;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Hyperf\Di\Annotation\Inject;
use function Hyperf\Support\make;

class InboxService
{
    #[Inject]
    protected AccountService $accountService;

    public function getLocalAccountByUsername($username)
    {
        return $this->accountService->getLocalAccountByUsername($username);
    }

    public function getAccountByUri($uri)
    {
        return $this->accountService->getAccountByUri($uri);
    }

    public function getStatusByUri($id)
    {
        return Status::where('uri', $id)->first();
    }

    public function updateOrCreateAccount($res)
    {
        $domain = parse_url($res['id'], PHP_URL_HOST);
        if (empty($res['preferredUsername']) && empty($res['nickname'])) {
            throw new InboxException('accountUpdateOrCreate-preferredUsername or nickname is null');
        }
        $username = (string) ($res['preferredUsername'] ?? $res['nickname']);
        $acct = "{$username}@{$domain}";

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

            $accountData['following_count'] = $this->getFollowingCount($accountData['following_uri']);
            $accountData['followers_count'] = $this->getFollowersCount($accountData['followers_uri']);

            if (!empty($res['publicKey']['publicKeyPem'])) {
                $accountData['public_key'] = $res['publicKey']['publicKeyPem'];
            }
            if (!empty($res['publicKey']['icon']['url']) || !empty($res['icon']['url'])) {
                $accountData['avatar_remote_url'] = $res['publicKey']['icon']['url'] ?? $res['icon']['url'];
                try {
                    $accountData['avatar'] = \Hyperf\Support\make(AttachmentServiceV3::class)->donwloadAndUpload($accountData['avatar_remote_url']);
                } catch (\Exception $e) {
                    Log::error('avatar download fail:'.$e->getMessage().', url:'.$accountData['avatar_remote_url']);
                }

            }

            if (!empty($res['publicKey']['image']['url']) || !empty($res['image']['url'])) {
                $accountData['profile_remote_image'] = $res['publicKey']['image']['url'] ?? $res['image']['url'];
                try {
                    $accountData['profile_image'] = \Hyperf\Support\make(AttachmentServiceV3::class)->donwloadAndUpload($accountData['profile_remote_image']);
                } catch (\Exception $e) {
                    Log::error('profile_image download fail:'.$e->getMessage().', url:'.$accountData['profile_remote_image']);
                }
            }

            if (!empty($res['publicKey']['tag']) || !empty($res['tag'])) {
                $this->storeEmoji($res['publicKey']['tag'] ?? $res['tag']);
            }

            if (!empty($res['publicKey']['attachment']) || !empty($res['attachment'])) {
                $accountData['fields'] = $res['publicKey']['attachment'] ?? $res['attachment'];
            }

            if (!empty($res['extra']['wallet_address'])) {
                $accountData['wallet_address'] = $res['extra']['wallet_address'];
            }

            return Account::updateOrCreate(['acct' => $acct], $accountData);
        } catch (\Exception $e) {
            throw new InboxException('accountUpdateOrCreate-exception '.$e->getMessage());
        }
    }

    public function createFollowRequest($actor, $target, $options = [])
    {
        FollowRequest::updateOrCreate([
            'account_id' => $actor->id,
            'target_account_id' => $target->id,
        ], [
            'activity' => \Hyperf\Collection\collect($options['payload'])->only(['id', 'actor', 'object', 'type'])->toArray()
        ]);
    }

    public function createFollow($actor, $target)
    {
        return Follow::updateOrCreate([
            'account_id' => $actor->id,
            'target_account_id' => $target->id,
        ]);
    }

    public function acceptRemoteFollowRequest($actor, $target, $follow, $id)
    {
        $accept = [
            '@context' => ActivityPubActivityInterface::CONTEXT_URL,
            'id'       => $target->permalink() . '#accepts/follows/' . $follow->id,
            'type'     => ActivityPubActivityInterface::TYPE_ACCEPT,
            'actor'    => $target->permalink(),
            'object'   => [
                'id'        => $id,
                'actor'     => $actor->permalink(),
                'type'      => ActivityPubActivityInterface::TYPE_FOLLOW,
                'object'    => $target->permalink()
            ]
        ];
        $this->sendSignedObject($target, $actor->inbox_uri, $accept);
    }

    public function rejectFollow($actor, $target)
    {
        $request = FollowRequest::where('account_id', $target->id)
            ->where('target_account_id', $actor->id)
            ->first();
        if (!$request) {
            return;
        }
        $request->delete();
    }

    public function undoFollow($account, $following)
    {
        Follow::where('account_id', $account->id)
            ->where('target_account_id', $following->id)
            ->delete();
        FollowRequest::where('account_id', $account->id)
            ->where('target_account_id', $following->id)
            ->delete();
        Notification::where('target_account_id', $following->id)
            ->where('account_id', $account->id)
            ->where('notify_type', Notification::NOTIFY_TYPE_FOLLOW)
            ->get()
            ->each(function ($item) {
                $item->delete();
            });
    }

    public function undoLike($accountId, $statusId)
    {
        StatusesFave::where('account_id', $accountId)
            ->where('status_id', $statusId)
            ->forceDelete();
        Notification::where('account_id', $accountId)
            ->where('status_id', $statusId)
            ->where('notify_type', Notification::NOTIFY_TYPE_FAVOURITE)
            ->get()
            ->each(function ($item) {
                $item->delete();
            });
    }

    public function undoAnnounceStatus($accountId, $targetAccountId, $statusId, $reblogId)
    {
        Status::where('account_id', $accountId)
            ->where('reblog_id', $statusId)
            ->delete();
        Notification::where('target_account_id', $targetAccountId)
            ->where('account_id', $accountId)
            ->where('status_id', $reblogId)
            ->where('type',Notification::NOTIFY_TYPE_REBLOG)
            ->forceDelete();
    }

    public function firstOrCreateStatusesFave($accountId, $targetAccountId, $statusId)
    {
        $statusFave = StatusesFave::where('account_id', $accountId)->where('status_id', $statusId)->first();
        if (!$statusFave) {
            StatusesFave::create([
                'account_id' => $accountId,
                'target_account_id' => $targetAccountId,
                'status_id' => $statusId
            ]);

            $this->addNotify($accountId, $targetAccountId, Notification::NOTIFY_TYPE_FAVOURITE, $statusId);
        }
    }

    public function sendSignedObject($account, $url, $body)
    {
        $headers = HttpSignature::sign($account, $url, $body, [
            'Content-Type'	=> 'application/activity+json; profile="'.ActivityPubActivityInterface::CONTEXT_URL.'"',
            'User-Agent'	=> ActivitypubService::getUa(),
        ]);

        try {
            $client = make(Client::class, [
                'timeout' => 15
            ]);
            $client->post($url, [
                'headers' => $headers,
                'json' => $body
            ]);

        } catch (\Exception $e) {
            throw new InboxException('request url['.$url.'] failed, err:'.$e->getMessage());
        }

    }

    public function acceptFollowRequest($actor, $target)
    {
        $request = FollowRequest::where('account_id', $actor->id)
            ->where('target_account_id', $target->id)
            ->first();
        if (!$request) {
            return;
        }

        $follow = Follow::firstOrCreate([
            'account_id' => $actor->id,
            'target_account_id' => $target->id,
        ]);
        Queue::send($follow->toArray(), Queue::TOPIC_FOLLOW);
        $request->delete();
    }

    public function acceptRelayRequest($id)
    {
        $relay = Relay::where('follow_activity_id', $id)->first();
        if (!$relay) {
            return;
        }

        $relay->state = Relay::STATE_ACCEPTED;
        $relay->save();
    }

    public function addNotify($accountId, $targetAccountId, $notifyType, $statusId = null)
    {
        Notification::firstOrCreate(
            [
                'target_account_id' => $targetAccountId,
                'account_id' => $accountId,
                'status_id' => $statusId,
                'notify_type' => $notifyType,
            ]
        );
    }

    public function createReport($targetAccountId, $statusIds, $content, $actor, $object, $uri)
    {
        return Report::create([
            'target_account_id' => $targetAccountId,
            'status_ids' => $statusIds,
            'rule_ids' => null,
            'comment' => $content,
            'meta' => [
                'actor' => $actor,
                'object' => $object
            ],
            'uri' => $uri
        ]);
    }

    protected function getFollowingCount($followingUri)
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

    protected function getFollowersCount($followersUri)
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

    protected function storeEmoji($tags)
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
            } catch (\Exception $e) {
                Log::error('CustomEmoji::updateOrCreate image_url download fail:'.$e->getMessage().', url:'.$tag['icon']['url']);
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

}