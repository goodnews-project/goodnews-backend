<?php

namespace App\Nsq\Consumer;

use App\Model\Follow;
use App\Model\Notification;
use App\Model\Status;
use App\Nsq\Queue;
use Hyperf\Nsq\Annotation\Consumer;
use Hyperf\Nsq\Result;

#[Consumer(topic: Queue::TOPIC_REBLOG, channel: 'event', name: 'status-create-listener', nums: 1)]
class StatusReBlogConsumer extends BaseConsumer
{
    public function handle($message)
    {
        $status = Status::findOrFail($message['newStatusId']);
        $parent = Status::findOrFail($message['statusId']);
        Notification::firstOrCreate([
            'account_id'        => $status->account_id,
            'target_account_id' => $parent,
            'notify_type'       => Notification::NOTIFY_TYPE_REBLOG,
            'status_id'         => $message['statusId']
        ]);
        return Result::ACK;
    }
}
