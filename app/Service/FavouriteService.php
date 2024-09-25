<?php

namespace App\Service;

use App\Model\Status;
use App\Model\StatusesFave;

class FavouriteService
{
    public function favoruites($account)
    {
        $favoruiteStatusIds = StatusesFave::where('account_id',$account['id'])
            ->latest()
            ->paginate()
            ->pluck('status_id')
            ->toArray(); 
        if(!$favoruiteStatusIds){
            return [];
        }
        return Status::withInfo($account)->whereIn('id',$favoruiteStatusIds)
            ->orderByRaw("FIELD(id, " . implode(',', $favoruiteStatusIds) . ")")
            ->get();
    }
}
