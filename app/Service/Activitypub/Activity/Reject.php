<?php

namespace App\Service\Activitypub\Activity;

use App\Entity\Contracts\ActivityPubActivityInterface;
use App\Model\FollowRequest;
use App\Util\ActivityPub\Helper;

class Reject extends Activity
{
    public function store()
    {
        $actor = $this->actor;
        $objActor = $this->object['actor'];
        $type = $this->object['type'];
        if ($type != ActivityPubActivityInterface::TYPE_FOLLOW) {
            return;
        }

        $actor = Helper::validateUrl($actor);
        $target = Helper::validateLocalUrl($objActor);

        if (!$actor || !$target) {
            return;
        }

        $actor = Helper::accountFetch($actor);
        $target = Helper::accountFetch($target);
        if (!$actor || !$target) {
            return;
        }

        $request = FollowRequest::where('account_id', $target->id)
            ->where('target_account_id', $actor->id)
            ->first();
        if (!$request) {
            return;
        }
        $request->delete();
    }

}