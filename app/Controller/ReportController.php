<?php

declare(strict_types=1);

namespace App\Controller;

use App\Middleware\AuthMiddleware;
use App\Request\Mastodon\ReportRequest;
use App\Service\AccountService;
use App\Service\Auth;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\Swagger\Annotation as OA;

#[OA\HyperfServer('http')]
#[Middleware(AuthMiddleware::class)]
class ReportController extends AbstractController
{
    #[Inject]
    protected AccountService $accountService;

    #[OA\Post(path:'/_api/v1/report',summary:'举报用户',tags:['举报用户'])]
    #[OA\Parameter(name: 'account_id', description: '被举报的用户ID', in : 'body', required: true)]
    #[OA\Parameter(name: 'status_ids', description: '推文ID数组', in : 'body', required: true)]
    #[OA\Parameter(name: 'comment', description: '备注', in : 'body', required: true)]
    #[OA\Parameter(name: 'forward', description: '是否同步给远端服务器 1 是', in : 'body', required: true)]
    #[OA\Parameter(name: 'forward_to_domains', description: '远端域名列表数据', in : 'body', required: true)]
    #[OA\Parameter(name: 'category', description: '分类', in : 'body', required: true)]
    #[OA\Parameter(name: 'rule_ids', description: '规则ID数组', in : 'body', required: true)]
    public function report(ReportRequest $request)
    {
        $payload = $request->validated() ;
        $this->accountService->report($payload, Auth::account()['id']);
        return $this->response->raw(null);
    }

}
