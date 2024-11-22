<?php

namespace App\Service\Activitypub\Activity;


class Like extends Activity
{
    public function store()
    {
        $actor = $this->actor;

        $account = $this->firstOrFetchAccount($actor);
        $obj = $this->object;

        $status = $this->fetchActivity($obj);
        if (!$status || !$account) {
            return;
        }

        $this->inboxService->firstOrCreateStatusesFave($account->id, $status->account->id, $status->id);
    }

}