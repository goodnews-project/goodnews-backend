<?php

declare(strict_types=1);

namespace App\Nsq\Consumer\ActivityPub;

use App\Entity\Contracts\ActivityPubActivityInterface;
use App\Model\Account;
use App\Model\Follow;
use App\Model\FollowRequest;
use App\Model\Notification;
use App\Nsq\Consumer\BaseConsumer;
use App\Nsq\Queue;
use App\Util\ActivityPub\Helper;
use App\Util\Log;
use Hyperf\Nsq\Annotation\Consumer;
use Hyperf\Nsq\Result;

#[Consumer(topic: Queue::TOPIC_FOLLOW_AP, channel: Queue::CHANNEL_ACTVITYPUB, name: 'FollowConsumer', nums: 1)]
class FollowConsumer extends BaseConsumer
{
    public function handle($data): ?string
    {
        if (!in_array($data['action'], [Follow::ACTION_FOLLOW, Follow::ACTION_UNFOLLOW])) {
            Log::error('Unsupported action：'.$data['action'], compact('data'));
            return Result::DROP;
        }

        if ($data['action'] == Follow::ACTION_FOLLOW) {
            $account = Account::findOrFail($data['account_id']);
            $targetAccount = Account::findOrFail($data['target_account_id']);
            $this->sendFollow($account, $targetAccount);
            return Result::ACK;
        }

        // unfollow
        $account = Account::findOrFail($data['account_id']);

        $targetAccount = Account::findOrFail($data['target_account_id']);

        $this->sendUndoFollow($account, $targetAccount);
        if (empty($targetAccount->domain)) {
            Notification::where('account_id', $account->id)
                ->where('target_account_id', $targetAccount->id)
                ->where('notify_type', Notification::NOTIFY_TYPE_FOLLOW)
                ->get()
                ->each(function ($item) {
                    $item->delete();
                });
        }

        FollowRequest::where('account_id', $account->id)
            ->where('target_account_id', $targetAccount->id)
            ->delete();

        return Result::ACK;
    }

    public function sendFollow(Account $account, Account $target)
    {
        if(!$target->isRemote() || !$account->isLocal()) {
            return;
        }

        $payload = [
            '@context'  => ActivityPubActivityInterface::CONTEXT_URL,
            'id'        => $account->permalink('#follow/'.$target->id),
            'type'      => ActivityPubActivityInterface::TYPE_FOLLOW,
            'actor'     => $account->permalink(),
            'object'    => $target->permalink()
        ];
        Log::info('FollowConsumer follow end', $payload);
        Helper::sendSignedObject($account, $target->inbox_uri, $payload);
    }

    public function sendUndoFollow(Account $account, Account $target)
    {
        if(!$target->isRemote() || !$account->isLocal()) {
            return;
        }

        $payload = [
            '@context'  => ActivityPubActivityInterface::CONTEXT_URL,
            'id'        => $account->permalink('#follow/'.$target->id.'/undo'),
            'type'      => ActivityPubActivityInterface::TYPE_UNDO,
            'actor'     => $account->permalink(),
            'object'    => [
                'id' => $account->permalink('#follows/'.$target->id),
                'actor' => $account->permalink(),
                'object' => $target->permalink(),
                'type' => ActivityPubActivityInterface::TYPE_FOLLOW
            ]
        ];

        Log::info('FollowConsumer unfollow end', $payload);
        Helper::sendSignedObject($account, $target->inbox_uri, $payload);
    }
}
