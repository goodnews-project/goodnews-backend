<?php

declare(strict_types=1);

namespace App\Nsq\Consumer\ActivityPub;

use App\Entity\Contracts\ActivityPubActivityInterface;
use App\Model\FollowRequest;
use App\Nsq\Consumer\BaseConsumer;
use App\Nsq\Queue;
use App\Util\ActivityPub\Helper;
use App\Util\Log;
use Hyperf\Nsq\Annotation\Consumer;
use Hyperf\Nsq\Result;

#[Consumer(topic: Queue::TOPIC_FOLLOW_REJECT, channel: Queue::CHANNEL_ACTVITYPUB, name: 'FollowRejectConsumer', nums: 1)]
class FollowRejectConsumer extends BaseConsumer
{
    public function handle($data): ?string
    {
        $followRequest = FollowRequest::findOrFail($data['id']);
        $account = $followRequest->account;
        $targetAccount = $followRequest->targetAccount;

        if(!$account->isRemote() || !$targetAccount->isLocal()) {
            return Result::DROP;
        }

        $payload = [
            '@context'  => ActivityPubActivityInterface::CONTEXT_URL,
            'type'      => ActivityPubActivityInterface::TYPE_REJECT,
            'id'		=> $followRequest->permalink(null, '#rejects'),
            'actor'     => $followRequest->targetAccount->permalink(),
            'object' 	=> [
                'type' 		=> ActivityPubActivityInterface::TYPE_FOLLOW,
                'id'        => $followRequest->activity && isset($followRequest->activity['id']) ? $followRequest->activity['id'] : null,
                'actor'		=> $followRequest->account->permalink(),
                'object'	=> $followRequest->targetAccount->permalink()
            ]
        ];
        Log::info(__FUNCTION__.' end', $payload);
        Helper::sendSignedObject($targetAccount, $account->inbox_uri, $payload);
        $followRequest->delete();
        return Result::ACK;
    }
}
