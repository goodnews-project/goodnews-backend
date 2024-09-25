<?php

namespace App\Controller\Mastodon\V1;

use App\Controller\AbstractController;
use App\Resource\Mastodon\StatusCollection;
use App\Service\FavouriteService;
use Hyperf\Di\Annotation\Inject;
use App\Service\Auth;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\Swagger\Annotation as OA;
use Richard\HyperfPassport\PassportAuthMiddleware;

#[OA\HyperfServer('http')]
class FavouriteController extends AbstractController
{
    #[Inject]
    protected FavouriteService $favoruiteService;

    #[OA\Get(path:'/api/v1/favourites',
        description: 'Statuses the user has favourited.',
        summary:'https://docs.joinmastodon.org/methods/favourites/#get', tags:['mastodon'])]
    #[Middleware(PassportAuthMiddleware::class)]
    public function index()
    {
        $account = Auth::passport();
        $favoruiteStatuses = $this->favoruiteService->favoruites($account);
        return StatusCollection::make($favoruiteStatuses);
    }
}
