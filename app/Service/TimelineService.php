<?php

namespace App\Service;

use App\Model\Hashtag;
use App\Model\ListAccount;
use App\Model\Status;
use App\Model\StatusesHashtag;
use App\QueryBuilder\QueryBuilder;

class TimelineService
{
    public function public($account, $pageSize = 20, QueryBuilder $queryBuilder = null)
    {
        $status = Status::withInfo($account);
        $status = $status->where('scope', Status::SCOPE_PUBLIC)
            ->with([
                'reply',
                'reply.account:id,username,display_name,avatar,domain,acct,note,profile_image,url,following_uri,followers_uri,followers_count,following_count'
            ])
            ->orderByDesc('id');
        if ($queryBuilder) {
            $queryBuilder->handle($status);
        }
        return $status->paginate($pageSize);
    }


    public function index($account = null, $pageSize = 20)
    {
        $status = Status::withInfo($account);
        return $status->where('scope', Status::SCOPE_PUBLIC)
            ->with([
                'reply',
                'reply.account:id,username,display_name,avatar,domain,acct,note,profile_image,url,following_uri,followers_uri,followers_count,following_count'
            ])->orderByDesc('id')->paginate($pageSize);
    }

    public function list($listId, $account)
    {
        $status = Status::withInfo($account);
        return $status->where('scope', Status::SCOPE_PUBLIC)
            ->whereIn(
                'account_id',
                ListAccount::select(['account_id'])
                    ->where('list_id', $listId)
            )
            ->with([
                'reply',
                'reply.account:id,username,display_name,avatar,domain,acct,note,profile_image,url,following_uri,followers_uri,followers_count,following_count'
            ])
            ->orderByDesc('id')
            ->paginate();
    }

    public function thisServer($account = null, $pageSize = 20)
    {
        $status = Status::withInfo($account);
        return $status->where([
            ['scope', Status::SCOPE_PUBLIC],
            ['is_local', 1]
        ])->with([
            'reply',
            'reply.account:id,username,display_name,avatar,domain,acct,note,profile_image,url,following_uri,followers_uri,followers_count,following_count'
        ])->orderByDesc('id')->paginate($pageSize);
    }

    public function hashTagStatuses($account, $pageSize = 20, QueryBuilder $queryBuilder = null)
    {
        $status = Status::withInfo($account);
        $status = $status->where('scope', Status::SCOPE_PUBLIC)
            ->with([
                'reply',
                'reply.account:id,username,display_name,avatar,domain,acct,note,profile_image,url,following_uri,followers_uri,followers_count,following_count'
            ])
            ->orderByDesc('id');
        if ($queryBuilder) {
            $queryBuilder->handle($status);
        }
        return $status->paginate($pageSize);
    }
}
