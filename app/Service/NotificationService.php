<?php

namespace App\Service;

use App\Model\Notification;
use App\Model\User;
use App\Nsq\NsqQueueMessage;

class NotificationService
{
    #[NsqQueueMessage]
    public function userConfirm($accountId)
    {
        $this->setFirstUserAsAdministrator();
        $targetAccountIds = User::where('role_id',1)->pluck('account_id');
        foreach($targetAccountIds as $targetAccountId) {
            Notification::create([
                'target_account_id'=> $targetAccountId,
                'account_id'=>$accountId,
                'notify_type'=> Notification::NOTIFY_TYPE_ADMIN_SIGN_UP
            ]);
        }
    }

    #[NsqQueueMessage]
    public function versionUpgrade($accountId, $statusId)
    {
        $this->setFirstUserAsAdministrator();
        $targetAccountIds = User::where('role_id',1)->pluck('account_id');
        foreach($targetAccountIds as $targetAccountId) {
            Notification::create([
                'target_account_id'=> $targetAccountId,
                'account_id'=>$accountId,
                'notify_type'=> Notification::NOTIFY_TYPE_SYSTEM,
                'status_id' => $statusId
            ]);
        }
    }

    public function setFirstUserAsAdministrator()
    {
        $user = User::first();
        if ($user->role_id == 1) {
            return;
        }

        $user->role_id = 1;
        $user->save();
    }
}
