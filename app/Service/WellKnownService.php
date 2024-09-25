<?php

declare(strict_types=1);

namespace App\Service;

use App\Model\Account;
use App\Model\Setting;
use App\Model\Status;
use App\Model\User;
use Carbon\Carbon;
use function Hyperf\Support\env;

class WellKnownService
{
    const NODE_VERSION = '0.0.1';
    const NODE_NAME = 'goodnews';

   public function webfinger($resource)
   {
       $resource = str_replace('acct:', '', $resource);
       $arr = explode('@', $resource);
       $username = $arr[0];
       $domain = $arr[1] ?? null;
       $username = str_replace('@', '', $username);;

       $q = Account::where('username', $username);
       if (empty($domain) || $domain == env('AP_HOST')) {
           $q->whereNull('domain');
       } else {
           $q->where('domain', $domain);
       }

       $account = $q->firstOrFail();
       $uris = UrisService::generateURIsForAccount($username);
       $uri = $account->uri ?: $uris['userURI'];
       return [
           'subject' => 'acct:'.$account->username.'@'.($domain ?: env('AP_HOST')),
           'links' => [
               [
                   'rel' => 'self',
                   'type' => 'application/activity+json',
                   'href' => $uri
               ],
           ],
       ];

   }

   public function nodeinfoRel()
   {
       return [
           'links' => [
               [
                   'rel' => 'http://nodeinfo.diaspora.software/ns/schema/2.0',
                   'href' => 'https://'.env('AP_HOST').'/.well-known/nodeinfo/2.0'
               ]
            ]
       ];
   }

    public function nodeinfo2()
    {
        $ttl = 43200;
        $users = CacheService::remember('api:nodeinfo:users', $ttl, function () {
            return User::count();
        });
        $statuses = CacheService::remember('api:nodeinfo:statuses', $ttl, function () {
            return Status::where('is_local', 1)->count();
        });

        $settingsJson = CacheService::remember('setting:nodeinfo:title_and_description', $ttl, function () {
            $settings = Setting::whereNull('settingable_id')->whereIn('key',[
                'site_title',
                'site_short_description',
            ])->pluck('value','key');
            return json_encode($settings, JSON_UNESCAPED_UNICODE);
        });
        $settings = json_decode($settingsJson, true);

        return [
            'version' => self::NODE_VERSION,
            'software' => [
                'name' => self::NODE_NAME,
                'version' => self::NODE_VERSION,
            ],
            'protocols' => ['activitypub'],
            'services' => [
                'outbound' => [],
                'inbound' => [],
            ],
            'usage' => [
                'users' => [
                    'total' => $users,
                    'activeMonth' => $this->activeUsersMonthly($ttl),
                    'activeHalfyear' => $this->activeUsersHalfYear($ttl),
                ],
                'localPosts' => $statuses
            ],
            'openRegistrations' => true,
            'metadata' => [
                'nodeName' => $settings['site_title'] ?? '',
                'nodeDescription' => $settings['site_short_description'] ?? '',
            ],
        ];
    }

    public function hostMeta()
    {
        $path = 'https://'.env('AP_HOST').'/.well-known/webfinger?resource={uri}';
        return '<?xml version="1.0" encoding="UTF-8"?><XRD xmlns="http://docs.oasis-open.org/ns/xri/xrd-1.0"><Link rel="lrdd" type="application/xrd+xml" template="'.$path.'"/></XRD>';
    }

    public function activeUsersMonthly($ttl)
    {
        return CacheService::remember('api:nodeinfo:active-users-monthly', $ttl, function () {
            return User::query()
                ->select('current_signin_at, updated_at')
                ->where('current_signin_at', '>', Carbon::now()->subWeeks(5))
                ->count();
        });
    }

    public function activeUsersHalfYear($ttl)
    {
        return CacheService::remember('api:nodeinfo:active-users-half-year', $ttl, function () {
            return User::query()
                ->select('current_signin_at, updated_at')
                ->where('current_signin_at', '>', Carbon::now()->subMonths(6))
                ->count();
        });
    }
}