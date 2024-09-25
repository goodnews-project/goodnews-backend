<?php

namespace App\Controller\Mastodon\V1\Timeline;
use App\Controller\AbstractController;
use App\QueryBuilder\Mastodon\TimelineQueryBuilder;
use App\Resource\Mastodon\StatusCollection;
use App\Service\Auth;
use App\Service\TimelineService;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\Swagger\Annotation as OA;
use Richard\HyperfPassport\PassportAuthMiddleware;


#[OA\HyperfServer('http')]
class PublicController extends AbstractController
{
    #[Inject]
    protected TimelineService $timelineService;

    #[OA\Get(path:'/api/v1/timelines/public',
        description: '社区：View public timeline',
        summary:'https://docs.joinmastodon.org/methods/timelines/#public', tags:['mastodon'])]
    public function public()
    {
        $payload = $this->request->all();
        $statuses = $this->timelineService->public(
            Auth::passport(),
            queryBuilder: (new TimelineQueryBuilder)->setInput($payload)
        );
        return StatusCollection::make($statuses->items());
    }    
}
