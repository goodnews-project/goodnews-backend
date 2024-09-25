<?php

declare(strict_types=1);

namespace App\Nsq\Consumer\ActivityPub;

use App\Entity\Contracts\ActivityPubActivityInterface;
use App\Model\Account;
use App\Model\Status;
use App\Model\StatusesFave;
use App\Nsq\Consumer\BaseConsumer;
use App\Nsq\Queue;
use App\Util\Log;
use Hyperf\Nsq\AbstractConsumer;
use Hyperf\Nsq\Annotation\Consumer;
use Hyperf\Nsq\Message;
use Hyperf\Nsq\Result;
use App\Util\ActivityPub\Helper;

#[Consumer(topic: Queue::TOPIC_LIKE, channel: Queue::CHANNEL_ACTVITYPUB, name: 'LikeConsumer', nums: 1)]
class LikeConsumer extends BaseConsumer
{
    const ACTION_LIKE = 'like';
    const ACTION_UNLIKE = 'unlike';

    public function handle($data): ?string
    {
        if (!in_array($data['action'], [self::ACTION_LIKE, self::ACTION_UNLIKE])) {
            Log::error('Unsupported action：'.$data['action'], compact('data'));
            return Result::DROP;
        }

        $fave = StatusesFave::find($data['id']);
        if ($data['action'] == self::ACTION_LIKE) {
            if (empty($fave)) {
                Log::error('Fave not found', compact('data'));
                return Result::DROP;
            }

            if (empty($fave->status)) {
                Log::error('Fave status not found', compact('data'));
                return Result::DROP;
            }

            $status = $fave->status;
            $payload = [
                '@context'  => ActivityPubActivityInterface::CONTEXT_URL,
                'id'        => $fave->account->permalink('#likes/'.$data['id']),
                'type'      => ActivityPubActivityInterface::TYPE_LIKE,
                'actor'     => $fave->account->permalink(),
                'object'    => $status->permalink()
            ];
            $url = $status->account->inbox_uri;
            Helper::sendSignedObject($fave->account, $url, $payload);

            Log::info('LikeConsumer like end', $payload);
            return Result::ACK;
        }

        // unlike
        $status = Status::findOrFail($data['status_id']);
        $account = Account::findOrFail($data['account_id']);
        $payload = [
            '@context'  => ActivityPubActivityInterface::CONTEXT_URL,
            'id'        => $account->permalink('#likes/'.$fave->id.'/undo'),
            'type'      => ActivityPubActivityInterface::TYPE_UNDO,
            'actor'     => $account->permalink(),
            'object'    => [
                'id' => $account->permalink('#likes/'.$fave->id),
                'actor' => $account->permalink(),
                'object' => $status->permalink(),
                'type' => ActivityPubActivityInterface::TYPE_LIKE
            ]
        ];

        $url = $status->account->inbox_uri;
        Helper::sendSignedObject($account, $url, $payload);
        Log::info('LikeConsumer unlike end', $payload);
        return Result::ACK;
    }
}
