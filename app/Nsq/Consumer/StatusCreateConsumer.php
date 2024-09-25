<?php

namespace App\Nsq\Consumer;

use App\Model\Account;
use App\Model\AccountSubscriberLog;
use App\Model\Follow;
use App\Model\Notification;
use App\Model\Status;
use App\Nsq\Queue;
use App\Resource\Mastodon\StatusResource;
use App\Service\Websocket;
use Carbon\Carbon;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Nsq\Annotation\Consumer;
use Hyperf\Nsq\Result;
use Hyperf\Redis\Redis;

#[Consumer(topic: Queue::TOPIC_STATUS_CREATE, channel: 'event', name: 'status-create-listener', nums: 1)]
class StatusCreateConsumer extends BaseConsumer
{

    public function handle($message)
    {
        $status = Status::findOrFail($message['id']);
        Websocket::pushStatusToFollower($status);

        // 订阅账号加未读数
        AccountSubscriberLog::where('target_account_id', $status->account_id)->get()->each(function (AccountSubscriberLog $log) {
            if ($log->state == AccountSubscriberLog::STATE_SUBSCRIBED) {
                $log->where('id', $log->id)->increment('unread_num');
            }
        });

        return Result::ACK;
    }
}
