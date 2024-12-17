<?php

declare(strict_types=1);

namespace App\Controller;

use App\Middleware\AuthMiddleware;
use App\Service\AccountService;
use App\Service\Auth;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\Swagger\Annotation as OA;

#[OA\HyperfServer('http')]
#[Middleware(AuthMiddleware::class)]
class BlockDomainController extends AbstractController
{

    #[Inject]
    protected AccountService $accountService;

    #[OA\Post(path:'/_api/v1/block_domain',summary:'屏蔽实例',tags:['屏蔽实例'])]
    #[OA\Parameter(name: 'domain', description: '被屏蔽的实例域名', in : 'body', required: true)]
    public function store()
    {
        $domain = $this->request->input('domain');
        $account = Auth::account();

        $this->accountService->blockDomain($account['id'], $domain);
        return $this->response->raw(null)->withStatus(204);
    }

    #[OA\Delete(path:'/_api/v1/block_domain',summary:'取消屏蔽实例',tags:['屏蔽实例'])]
    #[OA\Parameter(name: 'domain', description: '取消屏蔽实例的域名', in : 'body', required: true)]
    public function destroy()
    {
        $domain = $this->request->input('domain');
        $account = Auth::account();
        $this->accountService->unBlockDomain($account['id'], $domain);
        return $this->response->raw(null)->withStatus(204);
    }
}
