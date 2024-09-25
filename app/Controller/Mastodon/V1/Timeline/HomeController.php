<?php

namespace App\Controller\Mastodon\V1\Timeline;
use App\Controller\AbstractController;
use App\Resource\Mastodon\StatusCollection;
use App\Service\Auth;
use App\Service\TimelineService;
use Hyperf\Collection\Collection;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\Swagger\Annotation as OA;
use Richard\HyperfPassport\PassportAuthMiddleware;

#[OA\HyperfServer('http')]
class HomeController extends AbstractController
{
    #[Inject]
    protected TimelineService $timelineService;

    #[OA\Get(path:'/api/v1/timelines/home',
    description: 'View statuses from followed users and hashtags.',
    summary:'https://docs.joinmastodon.org/methods/timelines/#home', tags:['mastodon'])]
    #[Middleware(PassportAuthMiddleware::class)]
    public function index()
    {
        $account = Auth::passport();
        $status = $this->timelineService->public($account);
        return StatusCollection::make($status->items());
    }


    
}
