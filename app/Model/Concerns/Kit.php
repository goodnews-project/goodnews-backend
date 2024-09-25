<?php

declare(strict_types=1);

namespace App\Model\Concerns;

use App\Model\CustomEmoji;
use Carbon\Carbon;
use Hyperf\Stringable\Str;

trait Kit {
    public static string $emojiRe = "/(?<=[^[:alnum:]:]|\n|^):([a-zA-Z0-9_]{2,}):(?=[^[:alnum:]:]|$)/x";
    public function getEsBody($data): array
    {
        $body = [];
        foreach ($data as $k => $v) {
            if (array_key_exists($k, static::ES_PROPERTIES)) {
                $body[$k] = $v;
            }
        }
        return $body;
    }

    function getRemainingTime(Carbon|null $expiresAt)
    {
        if (is_null($expiresAt)) {
            return '投票已结束';
        }

        $now = Carbon::now();
        if ($now->gt($expiresAt)) {
            return '投票已结束';
        }

        $interval = $now->diff($expiresAt);

        $days = $interval->format('%a');
        $hours = $interval->format('%h');
        $minutes = $interval->format('%i');

        $fmtTime = '剩下';
        if ($days > 0) {
            return $fmtTime . $days . '天';
        }

        if ($hours > 0) {
            return $fmtTime . $hours . '小时';
        }

        return $fmtTime . $minutes . '分钟';
    }

    public function getActiveTime(Carbon|null $activeTime)
    {
        if (is_null($activeTime)) {
            return '';
        }

        $now = Carbon::now();
        if ($now->lt($activeTime)) {
            return '刚刚';
        }

        $interval = $now->diff($activeTime);

        $days = $interval->format('%a');
        $hours = $interval->format('%h');
        $minutes = $interval->format('%i');

        $fmtTime = '前';
        if ($days > 0) {
            return $days . '天'.$fmtTime;
        }

        if ($hours > 0) {
            return $hours . '小时'.$fmtTime;
        }

        return $minutes . '分钟'.$fmtTime;
    }

    public function getEmoji($content, $domain): array
    {
        return Str::of($content)->matchAll(self::$emojiRe)->unique()->map(function($match) use ($domain) {
            $q = CustomEmoji::query();
            if (empty($domain)) {
                $q->whereNull('domain');
            }
            return $q->where('shortcode', $match)->first();
        })->filter()->values()->toArray();
    }

    public function simpleGetImageMediaType($url)
    {
        return str_ends_with($url, '.png') ? 'image/png' : 'image/jpeg';
    }
}
