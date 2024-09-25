<?php

declare(strict_types=1);

namespace App\Controller;

use Hyperf\Swagger\Annotation as OA;
use App\Middleware\AuthMiddleware;
use App\Model\RewardLog;
use App\Model\RewardWithdrawLog;
use App\Request\WithdrawRequest;
use App\Service\Auth;
use App\Service\Web3Service;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\Middleware;

#[OA\HyperfServer('http')]
class WidthdrawController extends AbstractController
{

    #[OA\Get("/_api/v1/withdraw", summary: "获取提现记录", tags: ['web3钱包'])]
    #[OA\Parameter(name: 'address', description: '钱包地址', in: 'body', required: true)]
    #[Middleware(AuthMiddleware::class)]
    public function index()
    {
        $pageSize = (int)$this->request->input('pageSize',10);
        $address = $this->request->input('address');
        if (!$address) {
            return $this->response->json([]);
        }
        $withdrawLog = RewardWithdrawLog::where('address', $address)
            ->orderByDesc('id')
            ->paginate($pageSize);
        return $this->response->json($withdrawLog);
    }

    #[OA\Post('/_api/v1/withdraw', summary: "提现调用接口", tags: ['web3钱包'])]
    #[OA\Parameter(name: 'address', description: '钱包地址', in: 'body', required: true)]
    #[OA\Parameter(name: 'hash', description: '提现hash', in: 'body', required: true)]
    #[OA\Parameter(name: 'amount', description: '提现金额', in: 'body', required: true)]
    #[Middleware(AuthMiddleware::class)]
    public function withdraw(WithdrawRequest $requst)
    {
        $payload = $requst->validated();
        $account = Auth::account();
        RewardWithdrawLog::firstOrCreate(['hash' => $payload['hash']], [
            'address' => strtolower($payload['address']),
            'account_id' => $account['id'],
            'status' => RewardWithdrawLog::STATUS_PENDING,
            // 'block' => hexdec(substr($tx->blockNumber, 2)),
            'amount' => $payload['amount']
        ]);

        return $this->response->raw(null)->withStatus(204);
    }
}
