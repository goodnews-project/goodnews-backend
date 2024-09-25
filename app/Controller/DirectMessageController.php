<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Contracts\ActivityPubActivityInterface;
use App\Middleware\AuthMiddleware;
use App\Model\Account;
use App\Model\Attachment;
use App\Model\Conversation;
use App\Model\DirectMessage;
use App\Model\Notification;
use App\Model\Status;
use App\Nsq\Queue;
use App\Request\DirectMessageRequest;
use App\Resource\Mastodon\ConversationResource;
use App\Service\Auth;
use App\Service\SearchService;
use App\Service\Websocket;
use App\Util\ActivityPub\Helper;
use Carbon\Carbon;

use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\Swagger\Annotation as OA;
use function Hyperf\Translation\trans;

#[OA\HyperfServer('http')]
#[Middleware(AuthMiddleware::class)]
class DirectMessageController extends AbstractController
{
    public function __construct()
    {
        // todo To setLocale by account language
        Carbon::setLocale('zh-CN');
    }

    #[OA\Get(path: '/_api/v1/dm/search', summary: '搜索people or message', tags: ['私信'], parameters: [
        new OA\Parameter(name: 'keyword', description: '搜索关键字', in: 'query', required: true, schema: new OA\Schema(type: 'string')),
    ], responses: [
        new OA\Response(
            response: 200,
            description: '',
            content: new OA\JsonContent(
                type: 'array',
                items: new OA\Items(properties: [
                        new OA\Property(property: 'toId', title: '会话对方ID', type: 'integer'),
                        new OA\Property(property: 'avatar', title: '头像地址', type: 'string'),
                        new OA\Property(property: 'displayName', title: '显示名', type: 'string'),
                        new OA\Property(property: 'acct', title: '账号名', type: 'string'),
                        new OA\Property(property: 'timeAgo', title: '时间', type: 'string'),
                        new OA\Property(property: 'content', title: '消息内容', type: 'string'),
                    ], type: 'object')
            )
        )
    ]
    )]
    public function search()
    {
        $keyword = $this->request->input('keyword', '');
        $keyword = trim($keyword);
        if (empty($keyword)) {
            return $this->response->json(['msg' => trans('message.dm.input_keyword')])->withStatus(403);
        }

        // search people
        $accounts = SearchService::query(['q' => $keyword, 'resolve' => false, 'type' => 'accounts']);
        if ($accounts) {
            return \Hyperf\Collection\collect($accounts->items())->map(function ($item) {
                return [
                    'toId' => $item->id,
                    'avatar' => $item->getAvatarOrDefault(),
                    'displayName' => $item->display_name,
                    'acct' => $item->acct,
                ];
            });
        }

        // search conversations of contains keyword
        return $this->conversations($keyword);
    }

    #[OA\Get(path: '/_api/v1/dm/conversations', summary: '会话列表', tags: ['私信'], responses: [
        new OA\Response(
            response: 200,
            description: '',
            content: new OA\JsonContent(
                type: 'array',
                items: new OA\Items(properties: [
                    new OA\Property(property: 'id', title: '消息ID', type: 'integer'),
                    new OA\Property(property: 'avatar', title: '头像地址', type: 'string'),
                    new OA\Property(property: 'displayName', title: '显示名', type: 'string'),
                    new OA\Property(property: 'acct', title: '账号名', type: 'string'),
                    new OA\Property(property: 'timeAgo', title: '时间', type: 'string'),
                    new OA\Property(property: 'content', title: '消息内容', type: 'string'),
                    new OA\Property(property: 'time', title: '时间错', type: 'integer'),
                    new OA\Property(property: 'from_id', title: '发送者ID', type: 'integer'),
                    new OA\Property(property: 'has_unread', title: '是否有未读消息', type: 'bool'),
                ], type: 'object')
            )
        )
    ]
    )]
    public function conversations($message = '')
    {
        $accountId = Auth::account()['id'];

        $q = Conversation::query()->with(['status', 'directMessage'])->from('conversation as c');
        if ($message) {
            $q->leftJoin('status as s', 'c.status_id', '=', 's.id')->where('s.content', 'like', '%' . $message . '%')
                ->where('s.scope', Status::SCOPE_DIRECT)->select('s.content');
        }
        $dms = $q->where('c.to_id', $accountId)
            ->orWhere('c.from_id', $accountId)
            ->latest('c.status_id')
            ->select('c.*')
            ->paginate();
        $data = [];

        foreach ($dms as $dm) {
            $fromId = $accountId == $dm->to_id ? $dm->from_id : $dm->to_id;
            $account = Account::find($fromId);
            if (!$account) {
                continue;
            }

            if (empty($dm->status->content)) {
                continue;
            }

            if (empty($dm->directMessage)) {
                continue;
            }

            if (!empty($dm->deleted_account[$accountId]) && $dm->deleted_account[$accountId]['state'] == 'deleted') {
                continue;
            }
            $tmp = [
                'id' => $dm->id,
                'from_id' => $dm->from_id,
                'avatar' => $account->getAvatarOrDefault(),
                'displayName' => $account->display_name,
                'acct' => $account->acct,
                'timeAgo' => $dm->directMessage->created_at?->diffForHumans(null, null, true),
                'time' => $dm->directMessage->created_at?->getTimestamp(),
                'content' => $dm->status->content,
                'has_unread' => false
            ];
            if ($dm->directMessage->from_id != $accountId) {
                $tmp['has_unread'] = is_null($dm->directMessage->read_at);
            }

            $data[] = $tmp;
        }
        return $data;
    }

    #[OA\Post(path: '/_api/v1/dm/conversations/create', summary: '创建会话', tags: ['私信'])]
    #[OA\Parameter(name: 'toId', description: '对方ID', in: 'query', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Response(
        response: 200,
        description: '',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'id', title: '会话ID', type: 'integer')
            ],
            type: 'object'
        ),
    )]
    public function createConversations()
    {
        $toId = $this->request->input('toId');
        $fromId = Auth::account()['id'];
        if ($toId == $fromId) {
            return $this->response->json(['msg' => trans('message.dm.cant_dm_self')])->withStatus(403);
        }

        $m = Conversation::createUniquely($fromId, $toId);
        return ['id' => $m->id];
    }

    #[OA\Post(path: '/_api/v1/dm/conversations/delete', summary: '删除会话', tags: ['私信'])]
    #[OA\Parameter(name: 'id', description: '会话ID', in: 'query', required: true)]
    #[OA\Response(
        response: 200,
        description: '',
    )]
    public function deleteConversations()
    {
        $id = $this->request->input('id');
        $accountId = Auth::account()['id'];
        $c = Conversation::findOrFail($id);
        $deleted_account = [$accountId => ['deleted_at' => Carbon::now(), 'start_dm_id' => $c->dm_id, 'state' => 'deleted']];
        $c->deleted_account = array_merge((array) $c->deleted_account, $deleted_account);
        $c->save();
        return $this->response->raw(null);
    }

    #[OA\Get(path: '/_api/v1/dm/list/{cId}', summary: '私信对话', tags: ['私信'])]
    #[OA\Parameter(name: 'pageSize', description: '获取条数', in: 'query', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'minMessageId', description: '最早message id', in: 'query', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Response(
        response: 200,
        description: '',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'id', type: 'integer'),
                new OA\Property(property: 'displayName', type: 'string'),
                new OA\Property(property: 'acct', type: 'string'),
                new OA\Property(property: 'avatar', type: 'string'),
                new OA\Property(property: 'url', type: 'string'),
                new OA\Property(property: 'isLocal', type: 'boolean'),
                new OA\Property(property: 'domain', type: 'string'),
                new OA\Property(property: 'timeAgo', type: 'string'),
                new OA\Property(property: 'messages', type: 'array', items: new OA\Items(
                    properties: [
                        new OA\Property(property: 'id', type: 'integer'),
                        new OA\Property(property: 'isAuthor', type: 'boolean'),
                        new OA\Property(property: 'type', type: 'integer'),
                        new OA\Property(property: 'text', type: 'string'),
                        new OA\Property(property: 'media', type: 'string', nullable: true),
                        new OA\Property(property: 'timeAgo', type: 'string'),
                        new OA\Property(property: 'seen', type: 'boolean'),
                        new OA\Property(property: 'statusId', type: 'integer'),
                        new OA\Property(property: 'time', type: 'integer'),
                    ],
                    type: 'object'
                ))
            ],
            type: 'object'
        ),
    )]
    public function list($cId)
    {
        $pageSize = $this->request->input('pageSize', 20);
        $minMessageId = $this->request->input('minMessageId', 0);
        $currentAccountId = Auth::account()['id'];
        $c = Conversation::findOrFail($cId);

        $accountId = $c->to_id == $currentAccountId ? $c->from_id : $c->to_id;
        $account = Account::findOrFail($accountId);
        $q = DirectMessage::with(['status']);
        if ($minMessageId > 0) {
            $q->where('id', '<', $minMessageId);
        }

        if (!empty($c->deleted_account[$currentAccountId]['state']) == 'deleted') {
            $q->where('id', '>', $c->deleted_account[$currentAccountId]['start_dm_id']);
        }

        $res = $q->where(function ($q) use ($accountId, $currentAccountId) {
            return $q->where([['from_id', $accountId], ['to_id', $currentAccountId]
            ])->orWhere([['from_id', $currentAccountId], ['to_id', $accountId]]);
        })
            ->latest()
            ->limit($pageSize)
            ->get();
        $messages = $res->filter(function ($item) {
            return $item && $item->status;
        })->map(function ($item) use ($currentAccountId) {
            return [
                'id' => $item->id,
                'isAuthor' => $currentAccountId == $item->from_id,
                'type' => $item->dm_type,
                'text' => $item->status->content,
                'media' => $item->status->attachments()->first() ? $item->status->attachments()->first()?->url : null,
                'timeAgo' => $item->created_at ? $item->created_at->diffForHumans(null, null, true) : '',
                'time' => $item->created_at?->getTimestamp(),
                'seen' => $item->read_at != null,
                'statusId' => $item->status_id,
            ];
        });
        return [
            'id' => $account->id,
            'displayName' => $account->display_name,
            'acct' => $account->acct,
            'avatar' => $account->getAvatarOrDefault(),
            'url' => $account->url,
            'isLocal' => $account->isLocal(),
            'domain' => $account->domain,
            'timeAgo' => $c->directMessage?->created_at->diffForHumans(null, true, true),
            'time' => $c->directMessage?->created_at?->getTimestamp(),
            'messages' => $messages
        ];
    }

    #[OA\Post(path: '/_api/v1/dm/conversations/send', summary: '发送私信', tags: ['私信'])]
    #[OA\Parameter(name: 'to_id', description: '对方ID', in: 'query', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'message', description: '消息内容', in: 'query', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'type', description: '消息类型：1 文本，2 照片 3 视频', in: 'query', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'url', description: '图片地址, 上传图片时必须', in: 'query', required: false, schema: new OA\Schema(type: 'string'))]
    #[OA\Response(
        response: 200,
        description: '',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'id', type: 'string'),
                new OA\Property(property: 'isAuthor', type: 'boolean'),
                new OA\Property(property: 'statusId', type: 'string'),
                new OA\Property(property: 'type', type: 'integer'),
                new OA\Property(property: 'text', type: 'string'),
                new OA\Property(property: 'media', type: 'string', nullable: true),
                new OA\Property(property: 'timeAgo', type: 'string'),
                new OA\Property(property: 'seen', type: 'boolean'),
            ],
            type: 'object'
        ),
    )]
    public function send(DirectMessageRequest $directMessageRequest)
    {
        $payload = $directMessageRequest->validated();
        $account = Auth::account();
        $currAccountId = $account['id'];
        $toId = $payload['to_id'];
        $msg = $payload['message'];
        $dmType = $payload['type'];
        $fileUrl = $payload['url'] ?? 0;
        $fileType = $payload['file_type'] ?? 0;
        if ($toId == $currAccountId) {
            return $this->response->json(['msg' => trans('dm.cant_send_to_self')])->withStatus(403);
        }
        $recipient = Account::findOrFail($toId);

        $status = new Status;
        $status->account_id = $currAccountId;
        $status->content = $msg;
        $status->scope = Status::SCOPE_DIRECT;
        $status->reply_to_account_id = $recipient->id;
        $status->save();

        if ($fileUrl) {
            $media = new Attachment();
            $media->tid = $status->id;
            $media->from_table = Status::class;
            $media->url = $fileUrl;
            $media->file_type = $fileType;
            $media->save();
        }

        $dm = new DirectMessage;
        $dm->to_id = $recipient->id;
        $dm->from_id = $currAccountId;
        $dm->status_id = $status->id;
        $dm->dm_type = $dmType;
        $dm->save();

        Conversation::createUniquely($currAccountId, $recipient->id, [
            'dm_type' => $dm->dm_type,
            'status_id' => $status->id,
            'dm_id' => $dm->id,
        ]);

        if ($recipient->isLocal()) {
            $notification = new Notification();
            $notification->account_id = $currAccountId;
            $notification->target_account_id = $recipient->id;
            $notification->status_id = $status->id;
            $notification->notify_type = Notification::NOTIFY_TYPE_DM;
            $notification->save();

            if (Websocket::isSubscribeDirect($recipient->id)) {
                $dm->currAccountId = $currAccountId;
                Websocket::pushDirectMessage(ConversationResource::make($dm), $recipient->id);
            }
        }

        if ($recipient->isRemote()) {
            Queue::send(['dmId' => $dm->id], Queue::TOPIC_SEND_MESSAGE);
        }

        return [
            'id' => (string)$dm->id,
            'isAuthor' => $currAccountId == $dm->from_id,
            'statusId' => (string)$dm->status_id,
            'type' => $dm->dm_type,
            'text' => $dm->status->content,
            'media' => null,
            'timeAgo' => $dm->created_at->diffForHumans(null, null, true),
            'seen' => $dm->read_at != null,
        ];
    }

    #[OA\Delete(path: '/_api/v1/dm/conversations/delete/{id}', summary: '删除私信', tags: ['私信'])]
    #[OA\Response(
        response: 204,
        description: '操作成功'
    )]
    public function delete($id)
    {
        $accountId = Auth::account()['id'];

        $dm = DirectMessage::where('id', $id)->firstOrFail();

        if ($dm->from_id != $accountId) {
            return $this->response->json(['msg' => trans('message.dm.delete_owner_dm_only')])->withStatus(403);
        }

        $status = Status::where('account_id', $accountId)
            ->findOrFail($dm->status_id);

        $recipient = Account::findOrFail($dm->to_id);

        if ($recipient->isRemote()) {
            $this->remoteDelete($dm);
        }

        if (Conversation::where('status_id', $dm->status_id)->count()) {
            $cId = Conversation::getCId($dm->from_id, $dm->to_id);
            $latest = DirectMessage::where('c_id', $cId)
                ->latest()
                ->first();

            if ($latest->status_id == $dm->status_id) {
                Conversation::where('c_id', $cId)
                    ->update([
                        'updated_at' => $latest->updated_at,
                        'status_id' => $latest->status_id,
                        'dm_type' => $latest->dm_type,
                    ]);
            } else {
                Conversation::where('c_id', $cId)->delete();
            }
        }

        $status->delete();
        $dm->delete();
        return $this->response->raw(null);
    }

    #[OA\Get(path: '/_api/v1/dm/read', summary: '查看私信', tags: ['私信'])]
    #[OA\Parameter(name: 'account_id', description: '发送方账号ID', in: 'query', required: true)]
    #[OA\Parameter(name: 'sid', description: '对话内容里最新的消息ID', in: 'query', required: true)]
    #[OA\Response(
        response: 200,
        description: '',
        content: null,
    )]
    public function read()
    {
        $account_id = $this->request->input('account_id');
        $sid = $this->request->input('sid');
        $auth_account_id = Auth::account()['id'];

        $dms = DirectMessage::where('to_id', $auth_account_id)
            ->where('from_id', $account_id)
            ->where('status_id', '>=', $sid)
            ->whereNull('read_at')
            ->get();

        $now = Carbon::now();
        foreach ($dms as $dm) {
            $dm->read_at = $now;
            $dm->save();
        }

        return $dms->pluck('id');
    }

    public function remoteDelete(DirectMessage $dm)
    {
        $profile = $dm->author;
        $url = $dm->recipient->inbox_uri;

        $body = [
            '@context' => [
                ActivityPubActivityInterface::CONTEXT_URL,
            ],
            'id' => $dm->status->permalink('#delete'),
            'to' => [
                ActivityPubActivityInterface::PUBLIC_URL
            ],
            'type' => ActivityPubActivityInterface::TYPE_DELETE,
            'actor' => $dm->status->account->permalink(),
            'object' => [
                'id' => $dm->status->permalink(),
                'type' => ActivityPubActivityInterface::TYPE_TOMBSTONE
            ]
        ];

        Helper::sendSignedObject($profile, $url, $body);
    }
}
