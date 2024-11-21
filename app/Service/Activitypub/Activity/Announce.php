<?php

namespace App\Service\Activitypub\Activity;

use App\Model\Notification;
use App\Util\ActivityPub\Helper;
use Carbon\Carbon;

class Announce extends Activity
{

    public function store()
    {
        $actor = Helper::accountFetch($this->actor);
        $object = $this->object;

        $activity = $this->fetchActivity($object);
        $parent = $this->createStatus($actor, $activity);
        if (empty($parent)) {
            return;
        }

        $reblog_id = $parent['id'];

        $this->createStatus($actor->id, ['reblog_id' => $reblog_id, 'published_at' => Carbon::now()], ['attachment' => $parent['attachments'] ?? []]);

        Notification::firstOrCreate(
            [
                'target_account_id' => $parent['account_id'],
                'account_id' => $actor->id,
                'status_id' => $parent['id'],
                'notify_type' => Notification::NOTIFY_TYPE_REBLOG,
            ]
        );
    }
}