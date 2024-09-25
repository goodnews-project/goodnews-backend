<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */
namespace App\Controller;

use App\Middleware\AuthMiddleware;
use App\Model\Account;
use App\Model\Follow;
use App\Model\FollowHashtag;
use App\Model\Hashtag;
use App\Model\Status;
use App\Request\SearchRequest;
use App\Resource\StatusResource;
use App\Schema\AccountSchema;
use App\Schema\HashtagSchema;
use App\Schema\StatusSchema;
use App\Service\AccountService;
use App\Service\Auth;
use App\Service\SearchService;
use App\Service\UrisService;
use App\Util\ActivityPub\Helper;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\Swagger\Annotation as OA;

#[OA\HyperfServer('http')]
class IndexController extends AbstractController
{
    #[Inject]
    protected AccountService $accountService;

    #[OA\Get(path:'/_api/v1/search',summary:'账户和推文搜索',tags:['首页'], parameters: [
        new OA\Parameter(name: 'q', description: '搜索关键字', in : 'query', required: true, schema: new OA\Schema(type: 'string')),
        new OA\Parameter(name: 'type', description: '类型：accounts/statuses/hashtags', in : 'query', required: true, schema: new OA\Schema(type: 'string')),
        new OA\Parameter(name: 'resolve', description: '是否解析关键词', in : 'query', required: true, schema: new OA\Schema(type: 'string')),
    ], responses: [
        new OA\Response(response: 200, description: '', content: new OA\JsonContent(
            oneOf: [new OA\Schema(AccountSchema::class), new OA\Schema(StatusSchema::class)]
        )),
    ])]
    public function search(SearchRequest $searchRequest)
    {
        $data = $searchRequest->validated();
        return SearchService::query($data);
    }

    #[OA\Get(path:'/_api/v1/explore',summary:'hashtag 列表',tags:['首页'], responses: [
        new OA\Response(response: 200, description: '', content: new OA\JsonContent(
            oneOf: [new OA\Schema(HashtagSchema::class)]
        )),
    ])]
    public function explore()
    {
        return Hashtag::latest('id')->paginate(20);
    }

    #[OA\Get(path:'/_api/v1/explore/{tag}',summary:'含有hashtag的推文列表',tags:['首页'], responses: [
        new OA\Response(response: 200, description: '', content: new OA\JsonContent(
            oneOf: [new OA\Schema(StatusSchema::class)]
        )),
    ])]
    public function exploreStatusHashtags($tag)
    {
        return SearchService::exploreStatusHashtags($tag);
    }

    #[OA\Get(path:"/_api/v1/explore/{tag}/stat")]
    #[Middleware(AuthMiddleware::class)]
    public function getTagStatInfo($tag)
    {
        $accountId = Auth::account()['id'];
        $tag = urldecode($tag);
        $data = ['discussCount' => 0, 'statusTotal' => 0, 'statusTodayTotal' => 0, 'isFollow' => false];
        $hashtag = Hashtag::where('name', $tag)->first();
        if (empty($hashtag)) {
            return $data;
        }

        $data = SearchService::getStatInfoByHashtagId($hashtag->id);
        $data['isFollow'] = FollowHashtag::where('account_id', $accountId)->where('hashtag_id', $hashtag->id)->exists();
        return $data;
    }

    #[OA\Put(path:"/_api/v1/hashtag/{tag}/follow")]
    #[Middleware(AuthMiddleware::class)]
    public function followHashtag($tag)
    {
        $accountId = Auth::account()['id'];
        $tag = urldecode($tag);
        $hashtag = Hashtag::where('name', $tag)->first();
        if (empty($hashtag)) {
            return $this->response->json(['msg' => 'hashtag not found'])->withStatus(404);
        }

        FollowHashtag::updateOrCreate(['account_id' => $accountId, 'hashtag_id' => $hashtag->id]);
        return $this->response->raw(null)->withStatus(204);
    }

    #[OA\Put(path:"/_api/v1/hashtag/{tag}/un-follow")]
    public function unfollowHashtag($tag)
    {
        $accountId = Auth::account()['id'];
        $tag = urldecode($tag);
        $hashtag = Hashtag::where('name', $tag)->first();
        if (empty($hashtag)) {
            return $this->response->json(['msg' => 'hashtag not found'])->withStatus(404);
        }

        FollowHashtag::where('account_id', $accountId)->where('hashtag_id', $hashtag->id)->first()?->delete();
        return $this->response->raw(null)->withStatus(204);
    }

    #[OA\Post(path:"/_api/v1/remote-follow")]
    public function fetch()
    {
        $url = $this->request->input('url'); 
        $pwd = $this->request->input("pwd");
        if($pwd != '123!!@$$!$'){
            return $this->response->raw(null)->withStatus(403);
        }
        $account = Account::where('acct','twitter_bot')->first();
        if(!$account){
            $account = $this->accountService->create([
                'username'     => 'twitter_bot',
                'acct'         => 'twitter_bot',
                'display_name' => 'twitter_bot',
            ]);
        }
       

        $remoteAccount = Helper::accountFirstOrNew($url);
        Follow::firstOrCreate([
            'account_id'        => $account['id'],
            'target_account_id' => $remoteAccount['id']
        ]);
       return $this->response->json([
            'uri' => UrisService::generateURIsForAccount($account->username)['userURI']
       ]);
    }
}
