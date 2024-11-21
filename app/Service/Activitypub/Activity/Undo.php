<?php

namespace App\Service\Activitypub\Activity;

use App\Entity\Contracts\ActivityPubActivityInterface;
use App\Model\FollowRequest;
use App\Model\Notification;
use App\Model\Status;
use App\Model\StatusesFave;
use App\Util\ActivityPub\Helper;
use function Hyperf\Collection\last;

class Undo extends Activity
{

    public function store()
    {
        $actor = $this->actor;
        $account = Helper::accountFetch($actor);
        $obj = $this->object;

        if (!$account) {
            return;
        }
        if (!$obj || !is_array($obj) || !isset($obj['type'])) {
            return;
        }

        switch ($obj['type']) {
            case ActivityPubActivityInterface::TYPE_FOLLOW:
                $following = Helper::accountFetch($obj['object']);
                if (!$following) {
                    return;
                }

                \App\Model\Follow::where('account_id', $account->id)
                    ->where('target_account_id', $following->id)
                    ->delete();
                FollowRequest::where('account_id', $account->id)
                    ->where('target_account_id', $following->id)
                    ->delete();
                Notification::where('target_account_id', $following->id)
                    ->where('account_id', $account->id)
                    ->where('notify_type', Notification::NOTIFY_TYPE_FOLLOW)
                    ->get()
                    ->each(function ($item) {
                        $item->delete();
                    });
                break;

            case ActivityPubActivityInterface::TYPE_LIKE:
                $objectUri = $obj['object'];
                if (!is_string($objectUri)) {
                    if (is_array($objectUri) && isset($objectUri['id']) && is_string($objectUri['id'])) {
                        $objectUri = $objectUri['id'];
                    } else {
                        return;
                    }
                }
                $status = Helper::statusFirstOrFetch($objectUri);
                if (!$status) {
                    return;
                }
                StatusesFave::where('account_id', $account->id)
                    ->where('status_id', $status->id)
                    ->forceDelete();
                Notification::where('account_id', $status->account_id)
                    ->where('status_id', $status->id)
                    ->where('notify_type', Notification::NOTIFY_TYPE_FAVOURITE)
                    ->get()
                    ->each(function ($item) {
                        $item->delete();
                    });

                break;
            case ActivityPubActivityInterface::TYPE_ACCEPT:
                break;

            case ActivityPubActivityInterface::TYPE_ANNOUNCE:
                if (isset($obj['object'])) {
                    $obj = $obj['object'];
                }
                if (!is_string($obj)) {
                    return;
                }

                if (Helper::validateLocalUrl($obj)) {
                    $parsedId = last(explode('/', $obj));
                    $status = Status::find($parsedId);
                } else {
                    $status = Status::where('uri', $obj)->first();
                }
                if (!$status) {
                    return;
                }

                Status::where('account_id', $account->id)
                    ->where('reblog_id', $status->id)
                    ->delete();
                Notification::where('target_account_id', $status->account_id)
                    ->where('account_id', $account->id)
                    ->where('status_id', $status->reblog_id)
                    ->where('type',Notification::NOTIFY_TYPE_REBLOG)
                    ->forceDelete();
                break;
        }
    }
}