<?php

namespace App\Nsq\Consumer;

use App\Model\Account;
use App\Model\Follow;
use App\Model\Notification;
use App\Nsq\Queue;
use Hyperf\Nsq\Annotation\Consumer;
use Hyperf\Nsq\Result;

#[Consumer(topic: Queue::TOPIC_FOLLOW, channel: 'event', name: 'follow-listener', nums: 1)]
class FollowConsumer extends BaseConsumer
{
    public function handle($message)
    {
        $follow = Follow::findOrFail($message['id']);

        if ($follow->targetAccount->isLocal()) {
            Notification::create([
                'account_id'=> $message['account_id'],
                'target_account_id'=>$message['target_account_id'],
                'notify_type' => Notification::NOTIFY_TYPE_FOLLOW
            ]);
        }
        return Result::ACK;
    }
}
