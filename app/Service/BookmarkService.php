<?php

namespace App\Service;

use App\Model\Bookmark;
use App\Model\Status;

class BookmarkService
{
    public function bookmarks($account)
    {
        $bookmarkStatusIds = Bookmark::where('account_id',$account['id'])
                                    ->latest()
                                    ->paginate()
                                    ->pluck('status_id')
                                    ->toArray(); 
        if(!$bookmarkStatusIds){
            return [];
        }
        return Status::withInfo($account)
            ->orderByRaw("FIELD(id, " . implode(',', $bookmarkStatusIds) . ")")
            ->whereIn('id',$bookmarkStatusIds)->get(); 
    }
}
