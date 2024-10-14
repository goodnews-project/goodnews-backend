<?php

namespace App\Service\Activitypub;

use App\Entity\Contracts\ActivityPubActivityInterface;
use App\Model\Account;
use App\Service\DeliveryFailureTracker;
use App\Service\UrisService;
use App\Util\ActivityPub\HttpSignature;
use Hyperf\Logger\Logger;

class ActivitypubService
{
    public static function get($url)
    {
        $baseHeaders = [
            'Accept' => 'application/activity+json, application/ld+json',
        ];

        $headers = HttpSignature::instanceActorSign($url, false, $baseHeaders, 'get');
        $headers['Accept'] = 'application/activity+json, application/ld+json';
        $headers['User-Agent'] = self::getUa();

        try {
            $client = new \GuzzleHttp\Client();
            $res = $client->get($url, ['headers' => $headers]);
        } catch (\Exception $e) {
            // todo 失败的忽略
//            (new Logger('app'))->error($e->getMessage());
//            var_dump('ActivitypubService::get -> '.$url.'==='.$e->getMessage());
//            \Hyperf\Support\make(DeliveryFailureTracker::class, ['urlOrHost' => $url])->trackFailure();
            return null;
        }

        if($res->getStatusCode() != 200) {
            return null;
        }
        return $res->getBody()->getContents();
    }

    public static function getUa()
    {
        // todo 该ua暂停使用 'GoodNewsBot/1.0.0 (GoodNews/1.0; +'.\Hyperf\Support\env('HOST_URL').')';
        // use mastodon user agent
        return 'http.rb/5.1.1 (Mastodon/4.2.3; +https://mastodon.social/)';
    }

    public static function user($username = 'actor')
    {
        $account = Account::withTrashed()->where('username', $username)->whereNull('domain')->firstOrFail();
        $uris = UrisService::generateURIsForAccount($username);
        $actorTypeMap = Account::actorTypeMap;
        $reActorTypeMap = array_flip($actorTypeMap);
        $data = [
            '@context' => [
                ActivityPubActivityInterface::CONTEXT_URL,
                ActivityPubActivityInterface::SECURITY_URL,
            ],
            'id'                        => $account->uri ?: $uris['userURI'],
            'type'                      => $reActorTypeMap[$account->actor_type ?? Account::ACTOR_TYPE_PERSON],
            'following'                 => $account->following_uri ?: $uris['followingURI'],
            'followers'                 => $account->followers_uri ?: $uris['followersURI'],
            'inbox'                     => $account->inbox_uri ?: $uris['inboxURI'],
            'outbox'                    => $account->outbox_uri ?: $uris['outboxURI'],
            'preferredUsername'         => $account->username,
            'name'                      => $account->display_name,
            'summary'                   => $account->note,
            'url'                       => $account->url ?: $uris['userURL'],
            'manuallyApprovesFollowers' => (bool) $account->manually_approves_follower,
            'discoverable'              => true,
            'indexable'                 => true,
            'memorial'                  => false,
            'tag'                       => [],
            'attachment'                => [],
            'endpoints'                 => ['sharedInbox' => $uris['inboxURI']],
            'publicKey'                 => [
                'id'           => $account->public_key_uri ?: $uris['publicKeyURI'],
                'owner'        => $account->uri ?: $uris['userURI'],
                'publicKeyPem' => $account->public_key
            ],
            'published' => $account->created_at->toIso8601String(),

        ];
        $data['endpoints']['sharedInbox'] = $account->shared_inbox_uri ?: $uris['shareInboxUri'];

        if ($account->fields) {
            $data['attachment'] = $account->fields;
        }

        if ($account->suspended_at) {
            $data['discoverable'] = false;
            $data['indexable'] = false;
            $data['suspended'] = true;
        }

        $simpleGetImageMediaType = function ($url) {
            return str_ends_with($url, '.png') ? 'image/png' : 'image/jpeg';
        };

        if ($account->avatar) {
            $data['icon'] = [
                'type'      => 'Image',
                'mediaType' => $simpleGetImageMediaType($account->avatar),
                'url'       => $account->avatar
            ];
        }

        if ($account->profile_image) {
            $data['image'] = [
                'type'      => 'Image',
                'mediaType' => $simpleGetImageMediaType($account->profile_image),
                'url'       => $account->profile_image
            ];
        }

        if ($account->wallet_address) {
            $data['extra']['wallet_address'] = $account->wallet_address;
        }

        return $data;
    }
}