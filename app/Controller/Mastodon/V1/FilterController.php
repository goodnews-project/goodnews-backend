<?php

namespace App\Controller\Mastodon\V1;
use App\Controller\AbstractController;
use App\Model\Filter;
use App\Model\UserFilter;
use App\Resource\Mastodon\FilterResource;
use App\Resource\Mastodon\V1FilterCollection;
use App\Service\Auth;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\Swagger\Annotation as OA;
use Richard\HyperfPassport\PassportAuthMiddleware;

#[OA\HyperfServer('http')]
class FilterController extends AbstractController
{
    #[OA\Get(path:'/api/v1/filters',
        description: 'View your filters',
        summary:'https://docs.joinmastodon.org/methods/filters/#get-v1', tags:['mastodon'])]
    #[Middleware(PassportAuthMiddleware::class)]
    public function index()
    {
        $userFilters = UserFilter::where('account_id', Auth::passport()->id)->get();
        return V1FilterCollection::make($userFilters);
    }
}
