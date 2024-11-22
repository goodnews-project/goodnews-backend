<?php

namespace App\Service\Activitypub\Activity;

use App\Entity\Contracts\ActivityPubActivityInterface;
use App\Model\Status;
use App\Util\ActivityPub\Helper;
use function Hyperf\Collection\last;

class Undo extends Activity
{

    public function store()
    {
        $actor = $this->actor;
        $account = $this->firstOrFetchAccount($actor);
        $obj = $this->object;

        if (!$account) {
            return;
        }
        if (!$obj || !is_array($obj) || !isset($obj['type'])) {
            return;
        }

        switch ($obj['type']) {
            case ActivityPubActivityInterface::TYPE_FOLLOW:
                $following = $this->firstOrFetchAccount($obj['object']);
                if (!$following) {
                    return;
                }

                $this->inboxService->undoFollow($account, $following);
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
                $status = $this->firstOrFetchStatus($objectUri);
                if (!$status) {
                    return;
                }

                $this->inboxService->undoLike($account->id, $status->id);

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

                $this->inboxService->undoAnnounceStatus($account->id, $status->account_id, $status->id, $status->reblog_id);
        }
    }
}