<?php

declare(strict_types=1);

namespace App\Controller;

use App\Exception\AppException;
use App\Middleware\AuthMiddleware;
use App\Model\Account;
use App\Model\PayLog;
use App\Model\Status;
use App\Request\PayLogRequest;
use App\Request\RewardRequest;
use App\Service\Auth;
use App\Service\PayLogService;
use App\Service\Web3Service;
use Carbon\Carbon;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\Swagger\Annotation as OA;

#[OA\HyperfServer('http')]
class PayLogController extends AbstractController
{

    #[Inject]
    protected PayLogService $payLogService;

    #[OA\Post("/_api/v1/order/create",summary:"创建订单",tags:['web3钱包'])]
    #[OA\Parameter(name: 'type', description: '类型 1：解锁， 2：订阅 3：打赏', in : 'body', required: true)]
    #[OA\Parameter(name: 'reward_type', description: '打赏类型（type=3时必须） 1：作者， 2：推文', in : 'body', required: true)]
    #[OA\Parameter(name: 'amount', description: '打赏金额：type=3 时必须', in : 'body', required: false)]
    #[OA\Parameter(name: 'target_account_id', description: '作者ID:type=3 && reward_type=1时必须; 被订阅者ID：type=2 时必须', in : 'body', required: false)]
    #[OA\Parameter(name: 'status_id', description: '推文ID：type=3 && reward_type=2时必须；被解锁推文ID：type=1时必须', in : 'body', required: false)]
    #[OA\Parameter(name: 'plan_id', description: '计划ID：type=2 时必须', in : 'body', required: false)]
    #[Middleware(AuthMiddleware::class)]
    public function create(PayLogRequest $payLogRequest)
    {
        $payload = $payLogRequest->validated();
        $authAccountId = Auth::account()['id'];
        $payLog = $this->payLogService->create($authAccountId, $payload);
        return $this->response->raw($payLog->id);
    }

}
