<?php

namespace App\Service;

use App\Model\UnavailableDomain;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Redis\Redis;

class DeliveryFailureTracker
{
    #[Inject]
    protected Redis $redis;

    protected string $domain;

    const unavailableDomainKey = 'unavailable_domain';

    const FAILURE_DAYS_THRESHOLD = 7;

    public function __construct($urlOrHost)
    {
        $this->domain = str_starts_with($urlOrHost, 'https://') || str_starts_with($urlOrHost, 'http://') ? parse_url($urlOrHost, PHP_URL_HOST) : $urlOrHost;
    }

    public function trackFailure()
    {
        $this->redis->sAdd($this->exhaustedDeliveriesKey(), date('Ymd'));
        if ($this->days() >= self::FAILURE_DAYS_THRESHOLD) {
            UnavailableDomain::firstOrCreate(['domain' => $this->domain]);
            $this->redis->del(self::unavailableDomainKey);
        }
    }

    public function trackSuccess()
    {
        if (!$this->isMem()) {
            return;
        }

        $this->clearFailures();
        $unavailableDomain = UnavailableDomain::where('domain', $this->domain)->first();
        $unavailableDomain?->delete();
        $this->redis->del(self::unavailableDomainKey);
    }

    public function clearFailures()
    {
        if (!$this->isMem()) {
            return 0;
        }

        return $this->redis->del($this->exhaustedDeliveriesKey());
    }

    public function days()
    {
        return $this->redis->sCard($this->exhaustedDeliveriesKey()) ?: 0;
    }

    public function isMem(): bool
    {
        return $this->redis->sIsMember($this->exhaustedDeliveriesKey(), $this->domain);
    }

    public function exhaustedDeliveriesKey(): string
    {
        return 'exhausted_deliveries:'.$this->domain;
    }

    public function getUnavailableDomain()
    {
        if ($domainJsonList = $this->redis->get(self::unavailableDomainKey)) {
            return json_decode($domainJsonList, true);
        }
        $domainList = UnavailableDomain::pluck('domain')->toArray();
        if (empty($domainList)) {
            return [];
        }
        $this->redis->set(self::unavailableDomainKey, json_encode($domainList));
        return $domainList;
    }

    public function isUnavailable(): bool
    {
        return in_array($this->domain, $this->getUnavailableDomain());
    }

}