<?php

declare(strict_types=1);

namespace App\Controller\Mastodon\V1;

use App\Controller\AbstractController;
use App\Model\Account;
use App\Model\Bookmark;
use App\Model\CustomEmoji;
use App\Model\Hashtag;
use App\Model\Mute;
use App\Model\Status;
use App\Model\StatusesFave;
use App\Model\StatusesMention;
use App\Resource\Mastodon\AccountCollection;
use App\Resource\Mastodon\EmojiCollection;
use App\Resource\Mastodon\StatusCollection;
use App\Resource\Mastodon\TagResource;
use App\Service\AccountService;
use App\Service\Auth;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\Swagger\Annotation as OA;
use Richard\HyperfPassport\PassportAuthMiddleware;

#[OA\HyperfServer('http')]
class IndexController extends AbstractController
{
    #[Inject]
    protected AccountService $accountService;

    #[OA\Get(path:'/api/v1/suggestions',
        description: '为你推荐：Accounts the user has had past positive interactions with, but is not yet following.',
        summary:'https://docs.joinmastodon.org/methods/suggestions/#v1', tags:['mastodon'])]
    #[Middleware(PassportAuthMiddleware::class)]
    public function suggestions()
    {
        $account = Auth::passport();
        return AccountCollection::make($this->accountService->getSuggestions($account));
    }

   

 



}
