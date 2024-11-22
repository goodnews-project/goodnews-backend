<?php

namespace App\Service\Activitypub\Activity;

use App\Exception\InboxException;
use App\Model\Account;
use App\Model\FollowRequest;
use App\Model\Notification;

class Follow extends Activity
{
    public Account $actorAccount;
    public Account $targetAccount;

    public function validate()
    {
        $this->actorAccount = $this->firstOrFetchAccount($this->actor);
        $this->targetAccount = $this->firstOrFetchAccount($this->object);

        if ($this->actorAccount->isLocal() || !$this->targetAccount->isLocal()) {
            throw new InboxException('not about the account of this intance');
        }
    }

    public function store()
    {
        $actor = $this->actorAccount;
        $target = $this->targetAccount;

        if (in_array($actor->id, $target->blocks->pluck('target_account_id')->toArray())) {
            return;
        }

        if (\App\Model\Follow::where('account_id', $actor->id)->where('target_account_id', $target->id)->exists()) {
            return;
        }

        if ($target->manually_approves_follower) {
            if (FollowRequest::where('account_id', $actor->id)->where('target_account_id', $target->id)->exists()) {
                return;
            }

            $this->inboxService->createFollowRequest($actor, $target, ['payload' => $this->payload]);

            $this->inboxService->addNotify($actor->id, $target->id, Notification::NOTIFY_TYPE_FOLLOW_REQUEST);
            return;
        }

        $follow = $this->inboxService->createFollow($actor, $target);

        $this->inboxService->acceptRemoteFollowRequest($actor, $target, $follow, $this->id);

        $this->inboxService->addNotify($actor->id, $target->id,Notification::NOTIFY_TYPE_FOLLOW);
    }
}