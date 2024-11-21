<?php

namespace App\Service\Activitypub\Activity;

use App\Entity\Contracts\ActivityPubActivityInterface;
use App\Model\Account;
use App\Model\Status;
use App\Service\Activitypub\DeleteRemoteAccount;
use App\Service\Activitypub\DeleteRemoteStatus;
use App\Util\ActivityPub\Helper;

class Delete extends Activity
{

    public function store()
    {
        $actor = $this->actor;
        $obj = $this->object;
        if (is_string($obj) == true && $actor == $obj && Helper::validateUrl($obj)) {
            DeleteRemoteAccount::handle(null);
            return;
        }

        $id = $obj['id'];
        $account = Account::where('uri', $actor)->first();
        $type = $this->object['type'];
        switch ($type) {
            case ActivityPubActivityInterface::TYPE_PERSON:
                if (!$account || $account->isLocal()) {
                    return;
                }
                DeleteRemoteAccount::handle($account);
                break;

            case ActivityPubActivityInterface::TYPE_TOMBSTONE:
                if (!$account || $account->isLocal()) {
                    return;
                }
                $status = Status::where('account_id', $account->id)->where('uri', $id)->first();
                if (!$status) {
                    return;
                }
                DeleteRemoteStatus::handle($status);
                break;

            default:
                break;
        }
    }
}