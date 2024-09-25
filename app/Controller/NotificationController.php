<?php

declare(strict_types=1);

namespace App\Controller;

use App\Middleware\AuthMiddleware;
use App\Model\Notification;
use App\Service\Auth;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\Swagger\Annotation as OA;

#[OA\HyperfServer('http')]
#[Middleware(AuthMiddleware::class)]
class NotificationController extends AbstractController
{
    #[OA\Get(path:'/_api/v1/notifications',summary:'通知列表',tags:['通知'])]
    #[OA\Parameter(name: 'notify_type', description: '通知类型 同返回值', in : 'query', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Response(
        response: 200,
        description: 'read:1 已读 0未读 notify_type 1:关注 2:提及 3:转推 4:点赞 5:关注者发推 6:私信 11:用户注册 20:系统更新'
    )]
    public function index()
    {
        $account = Auth::account();
        $notifyType = $this->request->input('notify_type'); 
        $notification = Notification::with([
            'account', 
            'targetAccount', 
            'status',
            'status.attachments',
            'status.mentions',
            'status.polls',
            'status.previewCard',
        ]);
        if($account){
            $notification = $notification->with([
                'status.statusesFave'             => fn($q) => $q->where('account_id',$account['id']),
                'status.reblog'                   => fn($q) => $q->where('account_id',$account['id']),
                'status.bookmarked'               => fn($q) => $q->where('account_id',$account['id']),
                'status.account.follower'         => fn($q) => $q->where('account_id',$account['id']),
                'status.mentions.follower'        => fn($q) => $q->where('account_id',$account['id']),
                'status.pollVote'                 => fn($q) => $q->where('account_id', $account['id']),
                'account.follower'                => fn($q) => $q->where('account_id', $account['id']),
            ]);
        }
        return $notification->whereIn('notify_type',[
                Notification::NOTIFY_TYPE_FOLLOW,
                Notification::NOTIFY_TYPE_MENTION,
                Notification::NOTIFY_TYPE_REBLOG,
                Notification::NOTIFY_TYPE_FAVOURITE,
                Notification::NOTIFY_TYPE_STATUS,
                // Notification::NOTIFY_TYPE_DM,
                Notification::NOTIFY_TYPE_ADMIN_SIGN_UP,
                Notification::NOTIFY_TYPE_SYSTEM,
                Notification::NOTIFY_TYPE_POLL,
        ])
        ->latest()
        ->when($notifyType,fn($q) => $q->where('notify_type',$notifyType))
        ->where('target_account_id', $account['id'])->paginate();
    }
    #[OA\Put(path:"/_api/v1/notifications/read",summary:"设置为已读",tags:['通知'])]
    #[OA\Parameter(name: 'ids', description: '通知 id 数组', in : 'body', required: true, schema: new OA\Schema(type: 'string'))]
    public function read()
    {
       $ids = $this->request->input('ids'); 
       $account = Auth::account();
       Notification::whereIn('id',$ids)->where('target_account_id', $account['id'])->update([
            'read'=>1
       ]);
       return $this->response->raw(null)->withStatus(201);
    }
    #[OA\Put(path:"/_api/v1/notifications/read-all",summary:"设置为所有已读",tags:['通知'])]
    public function readAll()
    {
       $ids = $this->request->input('ids'); 
       $account = Auth::account();
       Notification::where('target_account_id', $account['id'])->update([
            'read'=>1
       ]);
       return $this->response->raw(null)->withStatus(201);
    }


    #[OA\Get(path:'/_api/v1/notifications/{id}',summary:'通知详情/置为已读',tags:['通知'])] 
    #[OA\Response(
        response: 200,
        description: ''
    )]
    public function show($id)
    {
        $notification = Notification::findOrFail($id);
        $notification->update(['read' => 1]);
        return $notification;
    }
     
}
