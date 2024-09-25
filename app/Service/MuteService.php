<?php

namespace App\Service;

use App\Model\Account;
use App\Model\Mute;

class MuteService
{
    public function mutes($account)
    {
        $muteAccountIds = Mute::where('account_id',$account['id'])->paginate()->pluck('target_account_id');
        return Account::whereIn('id',$muteAccountIds)->get();
    }
}
