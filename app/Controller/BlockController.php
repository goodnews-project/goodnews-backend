<?php

declare(strict_types=1);

namespace App\Controller;

use App\Middleware\AuthMiddleware;
use App\Model\Block;
use App\Request\BlockRequest;
use App\Service\AccountService;
use App\Service\Auth;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\Swagger\Annotation as OA;

#[OA\HyperfServer('http')]
#[Middleware(AuthMiddleware::class)]
class BlockController extends AbstractController
{

    #[Inject]
    protected AccountService $accountService;

    #[OA\Get(path:'/_api/v1/block',summary:'拉黑用户列表',tags:['拉黑用户'])]
    public function index()
    {
        $account = Auth::account(); 
        return Block::where('account_id',$account['id'])
                ->with('targetAccount')->latest()->paginate();
    }

    #[OA\Post(path:'/_api/v1/block',summary:'拉黑用户',tags:['拉黑用户'])]
    #[OA\Parameter(name: 'target_account_id', description: '被拉黑的用户ID', in : 'body', required: true)]
    public function store(BlockRequest $request)
    {
        $payload = $request->validated() ;
        $account = Auth::account();

        $this->accountService->block($account['id'], $payload['target_account_id']);
        return $this->response->raw(null)->withStatus(204);
    }

    #[OA\Delete(path:'/_api/v1/block',summary:'移除拉黑用户',tags:['拉黑用户'])]
    #[OA\Parameter(name: 'target_account_id', description: '被屏蔽的用户ID', in : 'body', required: true)]
    public function destroy()
    {
        $targetAccount = $this->request->input('target_account_id');
        $account = Auth::account();
        $this->accountService->unblock($account['id'], $targetAccount);
        return $this->response->raw(null)->withStatus(204);
    } 
}
