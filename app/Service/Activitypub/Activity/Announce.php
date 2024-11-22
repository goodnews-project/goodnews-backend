<?php

namespace App\Service\Activitypub\Activity;

use App\Model\Notification;

class Announce extends Activity
{
    public function store()
    {
        $actor = $this->firstOrFetchAccount($this->actor);
        $object = $this->object;

        $parent = $this->firstOrFetchStatus($object);
        if (empty($parent)) {
            return;
        }

        $this->inboxService->addNotify($actor->id, $parent['account_id'], Notification::NOTIFY_TYPE_REBLOG, $parent['id']);
    }
}