<?php

declare(strict_types=1);

namespace App\Controller;

use App\Middleware\AuthMiddleware;
use App\Model\Status;
use App\Model\StatusesFave;
use App\Nsq\Consumer\ActivityPub\LikeConsumer;
use App\Nsq\Queue;
use App\Service\Auth;
use App\Service\StatusesService;
use App\Service\UrisService;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\Swagger\Annotation as OA;
#[OA\HyperfServer('http')]
#[Middleware(AuthMiddleware::class)]
class FaveController extends AbstractController
{
    #[Inject]
    private StatusesService $statusesService;

    #[OA\Put(path:'/_api/v1/statuses/{statusId}/fave',summary:"点赞",tags:['推文'])]
    #[OA\Response(
        response: 204,
        description: ''
    )]
    public function fave($statusId)
    {
        $account = Auth::account();
        $this->statusesService->fave($account, $statusId);
       return $this->response->raw(null);
    }
    #[OA\Put(path:'/_api/v1/statuses/{statusId}/un-fave',summary:"取消点赞",tags:['推文'])]
    #[OA\Response(
        response: 204,
        description: ''
    )]
    public function unFave($statusId)
    {
        $account = Auth::account();
        $this->statusesService->unFave($account, $statusId);
       return $this->response->raw(null); 
    }
}
