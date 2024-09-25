<?php

declare(strict_types=1);

namespace App\Controller;

use App\Middleware\AuthMiddleware;
use App\Model\Follow;
use App\Model\Status;
use App\Resource\StatusPaginateResource;
use App\Resource\StatusResource;
use App\Service\Auth;
use App\Service\TimelineService;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Hyperf\Swagger\Annotation as OA;
use function Hyperf\Support\with;

#[OA\HyperfServer('http')]
class TimelineController extends AbstractController
{
    #[Inject]
    protected TimelineService $timelineService;

    #[OA\Get(path: '/_api/v1/timeline', summary: '首页推文列表', tags: ['推文'])]
    #[OA\Response(
        response: 200,
        description: 'unlock_log:解锁推文标记，为null则没解锁；subscriber_unlock_log：订阅作者标记，为null则没订阅'
    )]
    public function index()
    {
        $account = Auth::account();
        $statusList = $this->timelineService->index($account);
        return StatusPaginateResource::make($statusList);
    }

    #[OA\Get(path: '/_api/v1/timeline/list/{id}', summary: '根据id获取list内容', tags: ['推文'])]
    #[OA\Response(
        response: 200,
        description: ''
    )]
    public function list($id)
    {
        $account = Auth::account();
        $statusList = $this->timelineService->list($id, $account);
        return StatusPaginateResource::make($statusList);
    }

    #[OA\Get(path: '/_api/v1/local-timeline', summary: 'local 时间线', tags: ['推文'])]
    #[OA\Response(
        response: 200,
        description: ''
    )]
    public function thisServer()
    {
        $account = Auth::account();
        $statusList = $this->timelineService->thisServer($account);
        return StatusPaginateResource::make($statusList);
    }

    #[OA\Get(path: '/_api/v1/following', summary: '正在关注', tags: ['推文'])]
    #[OA\Response(
        response: 200,
        description: '需要登录后调用'
    )]
    #[Middleware(AuthMiddleware::class)]
    public function following()
    {
        $account = Auth::account();
        $status = Status::withInfo($account);
        if ($account) {
            $status->where(function ($query) use ($account) {
                $query->whereIn(
                    'account_id',
                    Follow::select('target_account_id')->where('account_id', $account['id'])
                )->orWhere('account_id', $account['id']);
            });
        }
        $status->with([
            'reply',
            'reply.account:id,username,display_name,avatar,domain,acct,note,profile_image,url,following_uri,followers_uri,followers_count,following_count'
        ]);
        $statusList = $status->where('scope', Status::SCOPE_PUBLIC)->orderByDesc('id')->paginate(30);
        return StatusPaginateResource::make($statusList);
    }
}
