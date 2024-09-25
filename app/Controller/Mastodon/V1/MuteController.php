<?php

namespace App\Controller\Mastodon\V1;

use App\Controller\AbstractController;
use App\Resource\Mastodon\AccountCollection;
use App\Service\Auth;
use App\Service\MuteService;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\Swagger\Annotation as OA;
use Richard\HyperfPassport\PassportAuthMiddleware;

#[OA\HyperfServer('http')]
class MuteController extends AbstractController
{
    #[Inject]
    protected MuteService $muteService;
    

    #[OA\Get(path:'/api/v1/mutes',
    description: 'Accounts the user has muted.',
    summary:'https://docs.joinmastodon.org/methods/mutes/#get', tags:['mastodon'])]
    #[Middleware(PassportAuthMiddleware::class)]
    public function index()
    {
        $account = Auth::passport();
        $accounts = $this->muteService->mutes($account);
        return AccountCollection::make($accounts);
    }
}
