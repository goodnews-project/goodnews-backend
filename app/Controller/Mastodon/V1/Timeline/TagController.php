<?php

namespace App\Controller\Mastodon\V1\Timeline;

use App\Controller\AbstractController;
use App\Model\Hashtag;
use App\QueryBuilder\Mastodon\TimelineHashtagQueryBuilder;
use App\Resource\Mastodon\StatusCollection;
use App\Service\Auth;
use App\Service\TimelineService;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Swagger\Annotation as OA;
#[OA\HyperfServer('http')]
class TagController extends AbstractController
{
    #[Inject]
    protected TimelineService $timelineService;
    #[OA\Get(path:'/api/v1/timelines/tag/{hashtag}',
        description: 'View public statuses containing the given hashtag.',
        summary:'https://docs.joinmastodon.org/methods/timelines/#tag', tags:['mastodon'])]
    
    public function index($hashtag)
    {
        $payload = $this->request->all();
        $payload['any'][] = $hashtag;
        $statuses = $this->timelineService->public(
            Auth::passport(),
            queryBuilder: (new TimelineHashtagQueryBuilder)->setInput($payload)
        );
        return StatusCollection::make($statuses->items());
    }
}
