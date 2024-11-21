<?php

namespace App\Service\Activitypub\Activity;

use App\Service\StatusesService;
use App\Util\ActivityPub\Helper;
use Carbon\Carbon;
use Hyperf\Di\Annotation\Inject;

abstract class Activity
{
    protected string $id;
    protected string $actor;
    protected mixed $object;
    protected array $payload;

    #[Inject]
    protected StatusesService $statusesService;

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

    public function createStatus($accountId, $activity, $options = [])
    {
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
        return $this->statusesService->create($accountId, $activity['content'], array_merge($data, $options));
    }
}