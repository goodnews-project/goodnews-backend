<?php

namespace App\Service\Activitypub\Activity;

use App\Entity\Contracts\ActivityPubActivityInterface;

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
            $this->inboxService->acceptRelayRequest($id);
            return;
        }

        $actor = $this->validateLocalUrl($actor);
        $target = $obj;

        if (!$actor || !$target) {
            return;
        }

        $actor = $this->firstOrFetchAccount($actor);
        $target = $this->firstOrFetchAccount($target);
        if (!$actor || !$target) {
            return;
        }

        $this->inboxService->acceptFollowRequest($actor, $target);
    }
}