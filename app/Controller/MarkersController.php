<?php

declare(strict_types=1);

namespace App\Controller;

use App\Middleware\AuthMiddleware;
use App\Model\DirectMessage;
use App\Model\FollowRequest;
use App\Model\Notification;
use App\Service\Auth;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\Swagger\Annotation as OA;

#[OA\HyperfServer('http')]
#[Middleware(AuthMiddleware::class)]
class MarkersController extends AbstractController
{
    #[OA\Get(path:'/_api/v1/markers',summary:"通知数量",tags:['通知'])]
    public function index()
    {
        $notificationCount = Notification::where([
            ['target_account_id',Auth::account()['id']],
            ['read',0]
        ])->whereIn('notify_type',[
            Notification::NOTIFY_TYPE_FOLLOW,
            Notification::NOTIFY_TYPE_MENTION,
            Notification::NOTIFY_TYPE_REBLOG,
            Notification::NOTIFY_TYPE_FAVOURITE,
            Notification::NOTIFY_TYPE_STATUS,
            Notification::NOTIFY_TYPE_ADMIN_SIGN_UP,
        ])->count();
        $directMessageCount = DirectMessage::where('to_id',Auth::account()['id'])
            ->whereNull('read_at')->count();
        $followRequestCount = FollowRequest::where('target_account_id', Auth::account()['id'])->count();
        return [
            'notifications'=> $notificationCount,
            'direct_messages'=> $directMessageCount,
            'follow_requests' => $followRequestCount
        ];
    }
}
