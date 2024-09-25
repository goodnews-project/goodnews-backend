<?php

declare(strict_types=1);

namespace App\Controller\Mastodon\V1;

use App\Controller\AbstractController;
use App\Model\Hashtag;
use App\Model\Status;
use App\Model\StatusHashtag;
use App\Resource\Mastodon\StatusCollection;
use App\Resource\Mastodon\TagCollection;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\Swagger\Annotation as OA;
use Richard\HyperfPassport\PassportAuthMiddleware;

#[OA\HyperfServer('http')]
#[Middleware(PassportAuthMiddleware::class)]
class TrendsController extends AbstractController
{
    #[OA\Get(path:'/api/v1/trends', description: '话题标签：是[/api/v1/trends/tags]这个接口的别名, 注意：目前是调用此接口', summary:'', tags:['mastodon'])]
    public function index()
    {
        return $this->tags();
    }

    #[OA\Get(path:'/api/v1/trends/statuses',
        description: 'Statuses that have been interacted with more than others.',
        summary:'https://docs.joinmastodon.org/methods/trends/#statuses', tags:['mastodon'])]
    public function statuses()
    {
        $limit = $this->request->input('limit', 20);
        $offset = $this->request->input('offset', 20);
        $status = Status::latest('reply_count')->latest('fave_count')->latest('reblog_count')->offset($offset)->limit($limit)->get();
        return StatusCollection::make($status);
    }

    #[OA\Get(path: '/api/v1/trends/links',
        description: 'Links that have been shared more than others.',
        summary: 'https://docs.joinmastodon.org/methods/trends/#links', tags: ['mastodon'])]
    public function links()
    {
        // todo
        return [];
    }

    #[OA\Get(path: '/api/v1/trends/tags',
        description: 'Tags that are being used more frequently within the past week. ',
        summary: 'https://docs.joinmastodon.org/methods/trends/#tags', tags: ['mastodon'])]
    public function tags()
    {
        $hashtagIds = StatusHashtag::where('created_at', '>', date('Y-m-d', strtotime('-7 days')))
            ->selectRaw('count(1) as n, hashtag_id')
            ->groupBy(['hashtag_id'])
            ->latest('n')
            ->pluck('hashtag_id');
        $hashtag = Hashtag::whereIn('id', $hashtagIds)->get();
        return TagCollection::make($hashtag);
    }
}
