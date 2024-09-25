<?php

namespace App\Controller\Mastodon\V1;
use App\Controller\AbstractController;
use App\Resource\Mastodon\AccountCollection;
use App\Service\Auth;
use App\Service\BlockService;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\Swagger\Annotation as OA;
use Richard\HyperfPassport\PassportAuthMiddleware;

#[OA\HyperfServer('http')]
class BlockController extends AbstractController
{

    #[Inject]
    protected BlockService $blockService;

    #[OA\Get(path:'/api/v1/blocks',
        description: 'View blocked users',
        summary:'https://docs.joinmastodon.org/methods/blocks/#get', tags:['mastodon'])]
    #[Middleware(PassportAuthMiddleware::class)]
    public function index()
    {
        $account = Auth::passport();
        $accounts = $this->blockService->blocks($account);
        return AccountCollection::make($accounts);
    }
}
