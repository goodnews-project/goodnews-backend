<?php

declare(strict_types=1);

namespace App\Controller;

use App\Middleware\AuthMiddleware;
use App\Model\Block;
use App\Model\Mute;
use App\Request\BlockRequest;
use App\Request\MuteRequest;
use App\Service\AccountService;
use App\Service\Auth;
use Carbon\Carbon;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\Swagger\Annotation as OA;

#[OA\HyperfServer('http')]
#[Middleware(AuthMiddleware::class)]
class MuteController extends AbstractController
{
    #[Inject]
    protected AccountService $accountService;

    #[OA\Get(path:'/_api/v1/mute',summary:'屏蔽用户列表',tags:['屏蔽用户'])]
    public function index()
    {
        $account = Auth::account(); 
        return Mute::where('account_id',$account['id'])
                ->with('targetAccount')->latest()->paginate();
    }

    #[OA\Post(path:'/_api/v1/mute',summary:'屏蔽用户',tags:['屏蔽用户'])]
    #[OA\Parameter(name: 'target_account_id', description: '被屏蔽的用户ID', in : 'body', required: true)]
    #[OA\Parameter(name: 'duration', description: '屏蔽时间 无限为 0', in : 'body', required: true)]
    public function store(MuteRequest $request)
    {
        $payload = $request->validated() ;
        $account = Auth::account();
        $this->accountService->mute($account['id'], $payload['target_account_id'], $payload['duration']);
        return $this->response->raw(null)->withStatus(204);
    }

    #[OA\Delete(path:'/_api/v1/mute',summary:'移除屏蔽用户',tags:['屏蔽用户'])]
    #[OA\Parameter(name: 'target_account_id', description: '被屏蔽的用户ID', in : 'body', required: true)]
    public function destroy()
    {
        $targetAccount = $this->request->input('target_account_id');
        $account = Auth::account();
        $this->accountService->unmute($account['id'], $targetAccount);
        return $this->response->raw(null)->withStatus(204);
    }
    
}
