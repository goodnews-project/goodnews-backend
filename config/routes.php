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

use App\Controller\AccountController;
use App\Controller\AttachmentController;
use App\Controller\DirectMessageController;
use App\Controller\FaveController;
use App\Controller\StatusesController;
use App\Controller\TimelineController;
use App\Controller\UserController;
use App\Controller\ActivityPubController;
use App\Controller\WellKnownController;
use App\Controller\IndexController;
use App\Controller\NotificationController;
use App\Controller\View\IndexController as ViewIndexController;
use App\Controller\View\AccountController as ViewAccountController;
use App\Controller\View\StatusController;
use App\Exception\HttpResponseException;
use App\Exception\InboxException;
use App\Middleware\ActivitypubMiddleware;
use App\Service\UrisService;
use App\Middleware\AuthMiddleware;
use App\Model\Account;
use App\Model\PreviewCardsStatus;
use App\Model\Status;
use App\Model\User;
use App\Resource\Mastodon\Model\AccountModel;
use App\Resource\Mastodon\Model\NodeInfoUsageModel;
use App\Resource\Mastodon\Model\NodeInfoUsersModel;
use App\Resource\Mastodon\StatusResource;
use App\Service\AttachmentService;
use App\Service\Auth;
use App\Service\NotificationService;
use App\Service\StatusesService;
use App\Service\UserService;
use App\Service\Websocket;
use FastRoute\Route;
use Hyperf\HttpServer\Router\Router;
use Qbhy\HyperfAuth\AuthManager;

use function Hyperf\Support\make;
use function Hyperf\ViewEngine\view;

Router::get('/embed/{acct}/status/{statusId}',[StatusController::class,'card']);
Router::get('/proxy', [AttachmentController::class, 'openUrl']);
Router::addGroup('/',function (){
    Router::get('t',function (){
        make(StatusesService::class)->qqqasfsaasf->viewStatuses(['105']);
        throw new HttpResponseException("not ",500) ;
    });

    Router::get('b',function (){
        return view('passport.authorize',[
            'client'=> (object)['name'=>'1','id'=>1],
            'scopes'=> [
                (object)['description'=>'ok'],
                (object)['description'=>'ok']
            ],
            'request'=> (object)['state'=>1],
            'authToken'=>1,
            'token'=>1
        ]);
    });
    Router::get('',[ViewIndexController::class,'index']);
    Router::get('{acct}',[ViewAccountController::class,'show']);
});


// Activitypub protocol api
Router::addGroup('/users', function (){
//    Router::get('/actor', [ActivityPubController::class, 'actor']);
    Router::get('/{username}', [ActivityPubController::class, 'user']);
    Router::get('/{username}/statuses/{statusId}', [ActivityPubController::class, 'status']);
    Router::get('/{username}/statuses/{statusId}/replies', [ActivityPubController::class, 'replies']);
    Router::post('/{username}/inbox', [ActivityPubController::class, 'inbox']);
    Router::get('/{username}/outbox', [ActivityPubController::class, 'outbox']);
    Router::get('/{username}/followers', [ActivityPubController::class, 'followers']);
    Router::get('/{username}/following', [ActivityPubController::class, 'following']);
    Router::get('/{username}/main-key', [ActivityPubController::class, 'publicKey']);
},['middleware'=> [ActivitypubMiddleware::class]]);
Router::post('/inbox', [ActivityPubController::class, 'sharedInbox']);

// well-known
Router::addGroup('/.well-known', function () {
    Router::get('/webfinger', [WellKnownController::class, 'webfinger']);
    Router::get('/nodeinfo', [WellKnownController::class, 'nodeinfoRel']);
    Router::get('/nodeinfo/2.0', [WellKnownController::class, 'nodeinfo2']);
    Router::get('/host-meta', [WellKnownController::class, 'hostMeta']);
});

Router::addServer('ws', function () {
    Router::get('/api/v1/streaming', 'App\Controller\WebSocketController');
});