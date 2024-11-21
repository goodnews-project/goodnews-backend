<?php

namespace App\Service\Activitypub\Activity;

use App\Model\Notification;
use App\Model\StatusesFave;
use App\Util\ActivityPub\Helper;

class Like extends Activity
{
    public function store()
    {
        $actor = $this->actor;

        $account = Helper::accountFetch($actor);
        $obj = $this->object;

        $status = $this->fetchActivity($obj);
        if (!$status || !$account) {
            return;
        }

        $statusFave = StatusesFave::where('account_id', $account->id)->where('status_id', $status->id)->first();
        if (!$statusFave) {
            StatusesFave::create([
                'account_id' => $account->id,
                'target_account_id' => $status->account->id,
                'status_id' => $status->id
            ]);
            Notification::firstOrCreate([
                'account_id'        => $account->id,
                'target_account_id' => $status->account->id,
                'status_id'         => $status->id,
                'notify_type'       => Notification::NOTIFY_TYPE_FAVOURITE
            ]);
        }
    }

}