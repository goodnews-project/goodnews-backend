<?php

namespace App\Service\Activitypub\Activity;

use App\Entity\Contracts\ActivityPubActivityInterface;
use App\Model\Account;
use App\Model\Conversation;
use App\Model\DirectMessage;
use App\Model\Notification;
use App\Model\Status;
use App\Util\ActivityPub\Helper;
use Carbon\Carbon;
use function Hyperf\Support\env;

class Create extends Activity
{
    public function store()
    {
        $activity = $this->object;
        $account = $this->firstOrFetchAccount($this->actor);
        $accountId = $account->id;
        $url = $this->id;
        if ($this->validateLocalUrl($url)) {
            return;
        }

        $to = $activity['to'] ?? [];
        $cc = $activity['cc'] ?? [];

        if ($activity['type'] == ActivityPubActivityInterface::TYPE_QUESTION) {
            $this->storePollStatus($accountId);
            return;
        }

        if (
            is_array($to) &&
            is_array($cc) &&
            count($to) == 1 &&
            count($cc) == 0 &&
            parse_url($to[0], PHP_URL_HOST) == env('AP_HOST')
        ) {
            $actor = $this->firstOrFetchAccount($this->actor);
            $activity = $this->object;
            $toArr = explode('/', $activity['to'][0]);
            $account = $this->inboxService->getLocalAccountByUsername(end($toArr));

            $msgText = strip_tags($activity['content']);

            if (str_starts_with($msgText, '@' . $account->username)) {
                $len = strlen('@' . $account->username);
                $msgText = substr($msgText, $len + 1);
            }

            if ($this->inboxService->getStatusByUri($activity['id'])) {
                return;
            }

            $this->object['content'] = $msgText;

            $this->storeDirectMessageStatus($actor, $account);
            return;
        }

        if ($activity['type'] == 'Note') {
            $this->createStatus($activity);
        }

    }

    public function storePollStatus($accountId)
    {
        $activity = $this->object;
        if(!isset($activity['endTime']) || !isset($activity['oneOf']) || !is_array($activity['oneOf']) || count($activity['oneOf']) > 4) {
            return null;
        }

        $options = \Hyperf\Collection\collect($activity['oneOf'])->map(function($option) {
            return $option['name'];
        })->toArray();


        $this->createStatus($activity, ['poll' => ['options' => $options], 'multiple' => false, 'expires_in' => Carbon::parse($activity['endTime'])->diffInSeconds(Carbon::now())]);

    }

    public function storeDirectMessageStatus(Account $actor, Account $to)
    {
        $account = $to;
        $activity = $this->fetchActivity($this->id);
        $status = $this->createStatus($activity, ['attachment' => $activity['attachment']]);

        $dm = new DirectMessage;
        $dm->to_id = $account->id;
        $dm->from_id = $actor->id;
        $dm->status_id = $status['id'];
        $dm->dm_type = DirectMessage::DM_TYPE_TEXT;
        $dm->save();

        Conversation::createUniquely($actor->id, $account->id, [
            'dm_type' => $dm->dm_type,
            'status_id' => $status['id'],
            'dm_id' => $dm->id,
        ]);

        if ($account->isLocal()) {
            $this->inboxService->addNotify($actor->id, $account->id, Notification::NOTIFY_TYPE_DM, $dm->status_id);
        }

    }

}