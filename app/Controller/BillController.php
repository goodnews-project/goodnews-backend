<?php

declare(strict_types=1);

namespace App\Controller;
use App\Middleware\AuthMiddleware;
use App\Model\PayLog;
use App\Request\BillRequest;
use App\Service\Auth;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\Swagger\Annotation as OA;

#[OA\HyperfServer('http')]
class BillController extends AbstractController
{

    #[OA\Get('/_api/bill', summary:"账单列表", tags:["_api", "bill"])]
    #[OA\Parameter(name: 'io_type', description: '收支类型 1：收入 2：支出, 多选1,2', in : 'query', required: false)]
    #[OA\Parameter(name: 'trade_type', description: '交易类型 1:解锁内容 2:订阅用户 3:打赏, 多选1,2,3', in : 'query', required: false)]
    #[OA\Response(
        response: 200,
        description: 'account_id=登录ID为支出，target_account_id=登录ID为收入； 
        type:交易类型 1:解锁内容 2:订阅用户 3:打赏；
        order_id:web3支付内容ID 该值当type=1 为status.id， type=2为订阅计划ID，type=3且reward_type=1为作者ID，reward_type=2为status.id。'
    )]
    #[Middleware(AuthMiddleware::class)]
    public function index(BillRequest $billRequest)
    {
        $payload = $billRequest->validated();
        $account = Auth::account();
        $accountId = $account['id'];
        $ioType = $payload['io_type'] ?? null;
        $tradeType = $payload['trade_type'] ?? null;
        $q = PayLog::query()
            ->with(['account:id,acct,avatar', 'targetAccount:id,acct,avatar'])
            ->where('state', PayLog::STATE_SUCCESS);
        $ioTypes = explode(',', $ioType);
        $ioTypes = array_unique($ioTypes);
        if (count($ioTypes) == 1) {
            $firstIoType = array_pop($ioTypes);
            $firstIoType == 1 ? $q->where('target_account_id', $accountId) : $q->where('account_id', $accountId);
        } else {
            $q->where(function ($q) use ($accountId) {
                $q->where('account_id', $accountId)->orWhere('target_account_id', $accountId);
            });
        }

        if ($tradeType) {
            $tradeTypes = explode(',', $tradeType);
            $q->whereIn('type', $tradeTypes);
        }
        return $q->latest()->paginate();
    }

}
