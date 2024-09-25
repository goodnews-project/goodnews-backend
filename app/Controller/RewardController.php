<?php

declare(strict_types=1);

namespace App\Controller;

use App\Exception\AppException;
use App\Middleware\AuthMiddleware;
use App\Model\Account;
use App\Model\PayLog;
use App\Model\RewardLog;
use App\Model\RewardWithdrawLog;
use App\Model\Status;
use App\Request\RewardRequest;
use App\Service\Auth;
use App\Service\Web3Service;
use Carbon\Carbon;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\Swagger\Annotation as OA;

#[OA\HyperfServer('http')]
class RewardController extends AbstractController
{


    #[OA\Get("/_api/v1/reward",summary:"获取打赏记录",tags:['web3钱包'])]
    #[OA\Parameter(name: 'address', description: '钱包地址', in : 'body', required: true)]
    #[Middleware(AuthMiddleware::class)]
    public function index()
    {
        $pageSize = (int)$this->request->input('pageSize',10);
        $address = $this->request->input('address');
        if(!$address){
            return $this->response->json([]);
        }
        $rewardLog = PayLog::where('send_addr',$address)
            ->orWhere('recv_addr',$address)
            ->where('type', PayLog::TYPE_REWARD)
            ->where('state', PayLog::STATE_SUCCESS)
            ->with(['account','targetAccount'])
            ->orderByDesc('id')
            ->paginate($pageSize);  
        // array_walk_recursive($rewardLog, function (&$value) { $value = (string)$value; });

        return $this->response->json($rewardLog);
    }

}
