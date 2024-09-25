<?php

namespace App\Controller\Mastodon\V1;
use App\Controller\AbstractController;
use App\Resource\Mastodon\StatusCollection;
use App\Service\BookmarkService;
use Hyperf\Di\Annotation\Inject;
use App\Service\Auth;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\Swagger\Annotation as OA;
use Richard\HyperfPassport\PassportAuthMiddleware;

#[OA\HyperfServer('http')]
class BookmarkController extends AbstractController
{
    #[Inject]
    protected BookmarkService $bookmarkService;

    #[OA\Get(path:'/api/v1/bookmarks',
    description: 'Statuses the user has bookmarked.',
    summary:'https://docs.joinmastodon.org/methods/bookmarks/#get', tags:['mastodon'])]
    #[Middleware(PassportAuthMiddleware::class)]
    public function index()
    {
        $account = Auth::passport();
        $favoruiteStatuses = $this->bookmarkService->bookmarks($account);
        return StatusCollection::make($favoruiteStatuses);
    }
}
