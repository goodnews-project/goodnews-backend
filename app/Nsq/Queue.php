<?php

namespace App\Nsq;

use App\Aspect\Annotation\ExecTimeLogger;
use App\Util\Log;
use Hyperf\Nsq\Nsq;

class Queue {
    const TOPIC_FOLLOW = 'topic_follow';
    const TOPIC_FOLLOW_AP = 'topic_follow_ap';
    const TOPIC_FOLLOW_ACCEPT = 'topic_follow_accept';
    const TOPIC_FOLLOW_REJECT = 'topic_follow_reject';
    const TOPIC_LIKE = 'topic_like';
    const TOPIC_STATUS_CREATE = 'topic_status_create';
    const TOPIC_STATUS_DELETE = 'topic_status_delete';
    const TOPIC_STATUS_UPDATE = 'topic_status_update';
    const TOPIC_STATUS_HAS_LINKS = 'topic_status_has_links';
    const TOPIC_REBLOG = 'topic_reblog';
    const TOPIC_SEND_MESSAGE = 'topic_send_message';
    const TOPIC_HTTP_REQUEST = 'topic_http_request';


    const CHANNEL_ACTVITYPUB = 'channel_actvitypub';
    const CHANNEL_EVENT = "channel_event";

    #[ExecTimeLogger(logParams:['topic'])]
    public static function publish(string|array $message, $topic, float $deferTime)
    {
        $message = self::builtInValueToStr($message);
        $nsq = \Hyperf\Support\make(Nsq::class);
        $bool = $nsq->publish($topic, $message, $deferTime);
        $context = compact('message', 'deferTime');
        $bool ? Log::info('publish success:', $context) : Log::error('publish fail:', $context);
        return $bool;
    }

    public static function send($data, $topic = 'hyperf', float $deferTime = 0.0)
    {
        return self::publish(json_encode($data, JSON_UNESCAPED_UNICODE), $topic, $deferTime);
    }

    public static function builtInValueToStr($arr)
    {
        if (!is_array($arr)) {
            return $arr;
        }

        $newArr = [];
        foreach ($arr as $k => $v) {
            if (is_scalar($v)) {
                $v = strval($v);
            }
            $newArr[$k] = $v;
        }
        return $newArr;
    }

}