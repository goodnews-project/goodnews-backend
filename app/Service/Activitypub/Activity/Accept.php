<?php

namespace App\Service\Activitypub\Activity;

use App\Entity\Contracts\ActivityPubActivityInterface;
use App\Model\Relay;
use App\Nsq\Queue;
use App\Request\FollowRequest;
use App\Util\ActivityPub\Helper;

class Accept extends Activity
{

    public function store()
    {
        $actor = $this->object['actor'];
        $obj = $this->object['object'];
        $type = $this->object['type'];
        $id = $this->object['id'];

        if ($type != ActivityPubActivityInterface::TYPE_FOLLOW) {
            return;
        }

        if ($obj == ActivityPubActivityInterface::PUBLIC_URL) {
            $relay = Relay::where('follow_activity_id', $id)->first();
            if (!$relay) {
                return;
            }
            $relay->state = Relay::STATE_ACCEPTED;
            $relay->save();
            return;
        }

        $actor = Helper::validateLocalUrl($actor);
        $target = Helper::validateUrl($obj);

        if (!$actor || !$target) {
            return;
        }

        $actor = Helper::accountFetch($actor);
        $target = Helper::accountFetch($target);
        if (!$actor || !$target) {
            return;
        }

        $request = FollowRequest::where('account_id', $actor->id)
            ->where('target_account_id', $target->id)
            ->first();
        if (!$request) {
            return;
        }


        $follow = \App\Model\Follow::firstOrCreate([
            'account_id' => $actor->id,
            'target_account_id' => $target->id,
        ]);
        Queue::send($follow->toArray(), Queue::TOPIC_FOLLOW);
        $request->delete();
    }
}