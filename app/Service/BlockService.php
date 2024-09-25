<?php

namespace App\Service;

use App\Model\Account;
use App\Model\Block;

class BlockService
{
    public function blocks($account)
    {
        $blockAccountIds= Block::where('account_id',$account['id'])
            ->paginate()->pluck('target_account_id');
        return Account::whereIn('id',$blockAccountIds)->get();
    }
}
