<?php

namespace App\Controller;

use App\Request\Pds\AuthorFeedRequest;
use App\Request\Pds\CreateSessionRequest;
use App\Request\Pds\FeedPostRequest;
use App\Resource\At\Pds\AccountResource;
use App\Resource\At\Pds\ProfileResource;
use App\Resource\At\Pds\StatusResource;
use App\Service\At\PdsService;
use App\Service\Auth;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Swagger\Annotation as OA;
#[OA\HyperfServer('http')]
class PdsController extends AbstractController
{
    #[Inject]
    protected PdsService $pdsService;

    #[OA\Get(path:'/xrpc/com.atproto.identity.resolveHandle',
        description: '允许其他节点通过 handle 查询用户的 DID',
        summary:'解析获取did',tags:['pds'])]
    #[OA\Parameter(name: 'handle', description: '', in: 'body', required: true, schema: new OA\Schema(type: 'string'))]
    public function resolveHandle()
    {
        $handle = $this->request->input('handle');
        $account = $this->pdsService->getAccountByHandle($handle);
        return ['did' => $account->did];
    }

    #[OA\Post(path:'/xrpc/com.atproto.server.createSession',
        description: '允许用户登录 PDS，获取身份令牌',
        summary:'创建会话',tags:['pds'])]
    public function createSession(CreateSessionRequest $createSessionRequest)
    {
        $payload = $createSessionRequest->validated();
        $token = $this->pdsService->createSession($payload['identifier'], $payload['password']);
        return [
            'accessJwt' => $token,
            'refreshJwt' => $token,
        ];
    }

    #[OA\Post(path:'/xrpc/com.atproto.server.refreshSession',
        description: '刷新身份令牌',
        summary:'刷新令牌',tags:['pds'])]
    public function refreshSession()
    {
        $token = $this->pdsService->refreshSession($this->request->input('refreshJwt'));
        return ['accessJwt' => $token];
    }

    #[OA\Get(path:'/xrpc/app.bsky.actor.searchActorsTypeahead',
        description: '用户的自动补全',
        summary:'搜索用户',tags:['pds'])]
    public function searchActorsTypeahead()
    {
        $accounts = $this->pdsService->searchActorsTypeahead($this->request->input('term'), $this->request->input('limit'));
        return AccountResource::collection($accounts)->toArray();
    }

    #[OA\Post(path:'/xrpc/app.bsky.feed.post',
        description: '',
        summary:'发布帖子',tags:['pds'])]
    public function feedPost(FeedPostRequest $feedPostRequest)
    {
        return $this->pdsService->feedPost(Auth::account()['id'],$feedPostRequest->validated());
    }

    #[OA\Get(path:'/xrpc/app.bsky.feed.getAuthorFeed',
        description: '',
        summary:'获取用户帖子',tags:['pds'])]
    public function getAuthorFeed(AuthorFeedRequest $authorFeedRequest)
    {
        $payload = $authorFeedRequest->validated();
        $status = $this->pdsService->getAuthorFeed($payload['author'], $payload['cursor'], $payload['limit']);
        return StatusResource::collection($status)->toArray();
    }

    #[OA\Get(path:'/xrpc/app.bsky.actor.getProfile',
        description: '',
        summary:'获取用户信息',tags:['pds'])]
    #[OA\Parameter(name: 'actor', description: '', in: 'body', required: true, schema: new OA\Schema(type: 'string'))]
    public function getProfile()
    {
        $actor = $this->pdsService->getProfile($this->request->input('did'));
        return ProfileResource::make($actor);
    }



}