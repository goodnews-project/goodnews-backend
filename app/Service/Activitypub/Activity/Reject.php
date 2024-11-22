<?php

namespace App\Service\Activitypub\Activity;

use App\Entity\Contracts\ActivityPubActivityInterface;

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

        $target = $this->validateLocalUrl($objActor);

        if (!$actor || !$target) {
            return;
        }

        $actor = $this->firstOrFetchAccount($actor);
        $target = $this->firstOrFetchAccount($target);
        if (!$actor || !$target) {
            return;
        }

       $this->inboxService->rejectFollow($actor, $target);
    }

}