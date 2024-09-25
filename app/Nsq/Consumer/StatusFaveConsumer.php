<?php

namespace App\Nsq\Consumer;

use App\Model\Follow;
use App\Model\Notification;
use App\Nsq\Queue;
use Hyperf\Nsq\Annotation\Consumer;
use Hyperf\Nsq\Result;

#[Consumer(topic: Queue::TOPIC_LIKE, channel: 'event', name: 'status-create-listener', nums: 1)]
class StatusFaveConsumer extends BaseConsumer
{
    public function handle($message)
    {
        Notification::firstOrCreate([
            'account_id'        => $message['account_id'],
            'target_account_id' => $message['target_account_id'],
            'status_id'         => $message['status_id'],
            'notify_type'       => Notification::NOTIFY_TYPE_FAVOURITE
        ]);
        return Result::ACK;
    }
}
