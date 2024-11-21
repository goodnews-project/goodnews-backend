<?php

namespace App\Service\Activitypub\Activity;

use App\Entity\Contracts\ActivityPubActivityInterface;
use App\Exception\InboxException;
use App\Model\FollowRequest;
use App\Model\Notification;
use App\Util\ActivityPub\Helper;

class Follow extends Activity
{

    public function store()
    {
        $actor = Helper::accountFetch($this->actor);
        $target = Helper::accountFetch($this->object);
        if (!$actor || !$target) {
            throw new InboxException('actor | target is empty:'.json_encode(compact('actor', 'target')));
        }

        if ($actor->isLocal() || !$target->isLocal()) {
            throw new InboxException('not about the account of this intance:'.json_encode(compact('actor', 'target')));
        }

        if (in_array($actor->id, $target->blocks->pluck('target_account_id')->toArray())) {
            return;
        }

        if (\App\Model\Follow::where('account_id', $actor->id)->where('target_account_id', $target->id)->exists()) {
            return;
        }

        if ($target->manually_approves_follower) {
            if (FollowRequest::where('account_id', $actor->id)->where('target_account_id', $target->id)->exists()) {
                return;
            }

            FollowRequest::updateOrCreate([
                'account_id' => $actor->id,
                'target_account_id' => $target->id,
            ], [
                'activity' => \Hyperf\Collection\collect($this->payload)->only(['id', 'actor', 'object', 'type'])->toArray()
            ]);

            Notification::create([
                'account_id' => $actor->id,
                'target_account_id' => $target->id,
                'notify_type' => Notification::NOTIFY_TYPE_FOLLOW_REQUEST,
            ]);
            return;
        }

        $follow = \App\Model\Follow::updateOrCreate([
            'account_id' => $actor->id,
            'target_account_id' => $target->id,
        ]);

        // send Accept to remote account
        $accept = [
            '@context' => ActivityPubActivityInterface::CONTEXT_URL,
            'id'       => $target->permalink() . '#accepts/follows/' . $follow->id,
            'type'     => ActivityPubActivityInterface::TYPE_ACCEPT,
            'actor'    => $target->permalink(),
            'object'   => [
                'id'        => $this->payload['id'],
                'actor'     => $actor->permalink(),
                'type'      => ActivityPubActivityInterface::TYPE_FOLLOW,
                'object'    => $target->permalink()
            ]
        ];
        Helper::sendSignedObject($target, $actor->inbox_uri, $accept);

        // add to notify
        Notification::create([
            'account_id' => $actor->id,
            'target_account_id' => $target->id,
            'notify_type' => Notification::NOTIFY_TYPE_FOLLOW,
        ]);
    }
}