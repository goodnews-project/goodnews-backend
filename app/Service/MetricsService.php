<?php

namespace App\Service;

use App\Model\Account;
use App\Model\Attachment;
use App\Model\Follow;
use App\Model\Instance;
use App\Model\Status;
use Carbon\Carbon;

class MetricsService
{
    public function domainAccountCount($domain)
    {
       return Account::where('domain',$domain)->count(); 
    }

    public function domainStatusCount($domain)
    {
        return Status::join('account','account.id','=','status.account_id','left')
            ->where('account.domain',$domain)
            ->count();
    }
    public function domainAttachmentSum($domain)
    {
       $query = Attachment::join('status','status.id','=','attachment.tid')
        ->join('account','account.id','=','status.account_id','left')
        ->where('account.domain','=',$domain);
        $fileSize = $query->clone()->sum('file_size');
        $thumbnailFileSize = $query->clone()->sum('thumbnail_file_size');

        return $fileSize + $thumbnailFileSize;
    }

    public function domainFollowCount($domain)
    {
       return Follow::join('account','account.id','=','follow.account_id','left')
                ->where('account.domain',$domain)
                ->count();
    }

    public function domainFollowerCount($domain)
    {
        return Follow::join('account','account.id','=','follow.target_account_id','left')
            ->where('account.domain',$domain)
            ->count(); 
    }

    public function domainReportCount($domain)
    {
        return 0;     
    }

    public function domainFollowerRank($domain,$take = 10)
    {
        return Account::with('follower')->where('domain',$domain)->orderBy('followers_count')
            ->take($take)
            ->get(['id','acct','username','domain','display_name','avatar','note','followers_count','following_count']);
    }

    public function domainAvailability(
        $domain,
        Carbon $startDate,
        Carbon $endData
    ){
        $dates = [];
        for($date = $startDate->copy(); $date->lte($endData); $date->addDay()) {
            $dates[] = ['date' => $date->format('Y-m-d'),'status' => true];
        }

        return $dates;
    }

    public function getInstanceByDomain($domain)
    {
        return Instance::where('domain', $domain)->first();
    }
}
