<?php

declare(strict_types=1);

namespace App\Controller\Mastodon\V2;

use App\Controller\AbstractController;
use App\Request\Mastodon\SearchRequest;
use App\Resource\Mastodon\AccountCollection;
use App\Resource\Mastodon\SearchResource;
use App\Service\AccountService;
use App\Service\Auth;
use App\Service\SearchService;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\Swagger\Annotation as OA;
use Richard\HyperfPassport\PassportAuthMiddleware;

#[OA\HyperfServer('http')]
class IndexController extends AbstractController
{
    #[Inject]
    protected AccountService $accountService;

    #[OA\Get(path:'/api/v2/search',
        description: 'Perform a search',
        summary:'https://docs.joinmastodon.org/methods/search/#v2', tags:['mastodon'])]
    public function search(SearchRequest $searchRequest)
    {
        $typeMap = [
            'accounts' => [],
            'hashtags' => [],
            'statuses' => [],
        ];
        $payload = $searchRequest->validated();
        if (empty($payload['type'])) {
            $accountsData = $payload;
            $tagsData = $payload;
            $statusData = $payload;
            $accountsData['type'] = 'accounts';
            $tagsData['type'] = 'hashtags';
            $statusData['type'] = 'statuses';
            $typeMap['accounts'] = SearchService::query($accountsData);
            $typeMap['hashtags'] = SearchService::query($tagsData);
            $typeMap['statuses'] = SearchService::query($statusData);
            return SearchResource::make($typeMap);
        }
        $typeMap[$payload['type']] = SearchService::query($payload);
        return SearchResource::make($typeMap);
    }

    #[OA\Get(path:'/api/v2/suggestions',
        description: '为你推荐：Accounts that are promoted by staff, or that the user has had past positive interactions with, but is not yet following.',
        summary:'https://docs.joinmastodon.org/methods/suggestions/#v2', tags:['mastodon'])]
    #[Middleware(PassportAuthMiddleware::class)]
    public function suggestions()
    {
        $account = Auth::passport();
        return AccountCollection::make($this->accountService->getSuggestions($account));
    }

    #[OA\Get(path:'/api/v2/instance',
        description: 'Obtain general information about the server.',
        summary:'https://docs.joinmastodon.org/methods/instance/#v2', tags:['mastodon'])]
    public function instance()
    {
        return [];
    }

}
