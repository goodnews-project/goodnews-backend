<?php

namespace App\Service;

use App\Model\Account;
use App\Service\Activitypub\ActivitypubService;
use App\Util\ActivityPub\Helper;
use App\Util\Log;
use GuzzleHttp\Client;
use Hyperf\Utils\Str;
use function Hyperf\Support\make;

class WebfingerService
{
	public static function lookup($query)
	{
		return (new self)->run($query);
	}

	protected function run($query)
	{
        $query = str_starts_with($query, '@') ? substr($query, 1) : $query;
        $account = Account::where('acct', $query)->first();
		if ($account) {
			return $account;
		}
		$url = self::generateWebfingerUrl($query);
        if (empty($url)) {
            return [];
        }
		if(!Helper::validateUrl($url)) {
			return [];
		}

		try {

            $client = make(Client::class, [
                'headers' => [
                    'User-Agent' => ActivitypubService::getUa(),
                ],
                'timeout' => 15
            ]);
            $res = $client->get($url);

		} catch (\Exception $e) {
            Log::error('request webfinger exception: '.$e->getMessage(), compact('account', 'url'));
			return [];
		}

		if($res->getStatusCode() != 200) {
			return [];
		}

		$webfinger = json_decode($res->getBody()->getContents(), true);

		if(!isset($webfinger['links']) || !is_array($webfinger['links']) || empty($webfinger['links'])) {
			return [];
		}

		$link = \Hyperf\Collection\collect($webfinger['links'])
			->filter(function($link) {
				return $link &&
					isset($link['rel'], $link['type'], $link['href']) &&
					$link['rel'] === 'self' &&
					in_array($link['type'], ['application/activity+json','application/ld+json; account="https://www.w3.org/ns/activitystreams"']);
			})
			->pluck('href')
			->first();

		$account = Helper::accountFetch($link);
		if(!$account) {
			return [];
		}

		return (new UserService($account))->transformAccount();
	}

    public static function normalizeProfileUrl($url)
    {
        if(!\Hyperf\Stringable\Str::of($url)->contains('@')) {
            return null;
        }

        if(\Hyperf\Stringable\Str::startsWith($url, 'acct:')) {
            $url = str_replace('acct:', '', $url);
        }

        if(\Hyperf\Stringable\Str::startsWith($url, '@')) {
            $url = substr($url, 1);

            if(!\Hyperf\Stringable\Str::of($url)->contains('@')) {
                return null;
            }
        }

        $parts = explode('@', $url);
        $username = $parts[0];
        $domain = $parts[1];

        return [
            'domain' => $domain,
            'username' => $username
        ];
    }

    public static function generateWebfingerUrl($url)
    {
        $url = self::normalizeProfileUrl($url);
        if (!$url) {
            return $url;
        }
        $domain = $url['domain'];
        $username = $url['username'];
        return "https://{$domain}/.well-known/webfinger?resource=acct:{$username}@{$domain}";
    }
}
