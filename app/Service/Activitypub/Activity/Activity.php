<?php

namespace App\Service\Activitypub\Activity;

use App\Exception\InboxException;
use App\Service\InboxService;
use App\Service\StatusesService;
use App\Util\ActivityPub\Helper;
use Carbon\Carbon;
use Hyperf\Collection\Arr;
use Hyperf\Di\Annotation\Inject;
use function Hyperf\Support\env;

abstract class Activity
{
    protected string $id;
    protected string $actor;
    protected mixed $object;
    protected array $payload;

    #[Inject]
    protected StatusesService $statusesService;

    #[Inject]
    protected InboxService $inboxService;

    public function __construct(array $payload)
    {
        $this->id = $payload['id'];
        $this->actor = $payload['actor'];
        $this->object = $payload['object'];
        $this->payload = $payload;
    }

    public function handle()
    {
        $this->validate();
        $this->store();
    }

    public function validate()
    {
    }

    public function fetchActivity($url)
    {
        $res = Helper::fetchFromUrl($url);

        return isset($res['object']) ? $res : ['object' => $res];
    }

    public function firstOrFetchStatus($url)
    {
        $status = $this->inboxService->getStatusByUri($url);
        if (empty($status)) {
            $activity = $this->fetchActivity($url);
            return $this->createStatus($activity);
        }
        return $status;
    }

    public function createStatus($activity, $options = [])
    {
        $actor = $this->firstOrFetchAccount($activity['actor']);
        $url = $activity['url'] ?? null;

        $data = [
            'url' => $activity['url'] ?? null,
            'uri' => $activity['id'] ?? null,
            'published_at' => Carbon::parse($activity['published'])->toDatetimeString(),
            'is_local' => false,
            'is_sensitive' => Helper::getSensitive($activity, $url),
            'scope' => Helper::getScope($activity, $url),
            $options
        ];
        return $this->statusesService->create($actor->id, $activity['content'], array_merge($data, $options));
    }

    public function firstOrFetchAccount($url)
    {
        $host = parse_url($url, PHP_URL_HOST);
        if(env('AP_HOST') == $host) {
            $username = Arr::last(explode('/', $url));
            return $this->inboxService->getLocalAccountByUsername($username);
        }

        $account = $this->inboxService->getAccountByUri($url);
        if (empty($account)) {
            $res = $this->firstOrFetchAccount($url);
            $account = $this->inboxService->updateOrCreateAccount($res);
        }

        if (empty($account)) {
            throw new InboxException('account created fail, account:'.$account.', url:'.$url);
        }

        if($account->isLocal()) {
            throw new InboxException('It is local-account, account:'.$account.', url:'.$url);
        }

        if(empty($account->last_webfingered_at) || $account->last_webfingered_at->lt(Carbon::now()->subMinutes(5))) {
            $res = $this->firstOrFetchAccount($url);
            $this->inboxService->updateOrCreateAccount($res);
        }
        return $account;
    }

    public function validateLocalUrl($url)
    {
        $domain = env('AP_HOST');
        $host = parse_url($url, PHP_URL_HOST);
        return strtolower($domain) === strtolower($host) ? $url : false;
    }
}