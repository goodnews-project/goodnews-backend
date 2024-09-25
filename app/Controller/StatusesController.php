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

use App\Exception\AppException;
use App\Middleware\AuthMiddleware;
use App\Model\Account;
use App\Model\AccountWarning;
use App\Model\Bookmark;
use App\Model\PayLog;
use App\Model\Poll;
use App\Model\Status;
use App\Model\StatusEdit;
use App\Model\StatusUnlockLog;
use App\Model\UserFilter;
use App\Nsq\Queue;
use App\Request\StatuesRequest;
use App\Resource\StatusPaginateResource;
use App\Service\Auth;
use App\Service\StatusesService;
use App\Service\UnlockLog;
use App\Service\Web3Service;
use Carbon\Carbon;
use Hyperf\DbConnection\Db;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\Redis\Redis;
use Hyperf\Swagger\Annotation as OA;
use App\Resource\StatusResource;

#[OA\HyperfServer('http')]
class StatusesController extends AbstractController
{
    #[Inject]
    private StatusesService $statusesService;


    #[Inject]
    protected Redis $redis;

    #[OA\Get(path: '/_api/v1/statuses/{acct}', summary: '获取推文列表', tags: ['推文'])]
    #[OA\Parameter(name: 'type', description: '传值:with_replies,tweets,media,likes', in: 'query')]
    #[OA\Response(
        response: 200,
        description: ''
    )]
    public function index($acct)
    {
        $type = $this->request->input('type', 'with_replies');
        $account = Account::where('acct', $acct)->firstOrFail();
        $statusList = $this->statusesService->statuses($account, $type, Auth::account());
        return StatusPaginateResource::make($statusList);
    }

    #[OA\Post(path: '/_api/v1/statuses', summary: '发布推文', tags: ['推文'])]
    #[OA\Parameter(name: 'content', description: '推文内容', in: 'query', required: true)]
    #[OA\Parameter(name: 'reply_to_id', description: '回复推文ID', in: 'query')]
    #[OA\Parameter(name: 'who_can_reply', description: '谁可以回复 0：所有人 1：仅关注的人 2：仅提及的人', in: 'query')]
    #[OA\Parameter(name: 'scope', description: '可见范围：1：公开 2：仅关注者可见 4：不公开', in: 'query')]
    #[OA\Parameter(name: 'poll', description: '投票选项,数组', in: 'query', example: '{"options":["options1","options2"],"expires_in":86400,"multiple":false}')]
    #[OA\Parameter(name: 'is_sensitive', description: '是否敏感内容 1：敏感 0：不敏感', in: 'query')]
    #[OA\Parameter(name: 'enable_activitypub', description: '是否推送, 1:推送 0 不推送，默认1', in: 'query')]
    #[OA\Parameter(name: 'attachments', description: '附件数组,通过/_api/v1/attachment 接口获取', in: 'query', example: '[{"id":1},{"id":2}]')]
    #[OA\Parameter(name: 'fee', description: '设置费用，string', in: 'query')]
    #[OA\Response(
        response: 200,
        description: '',
    )]
    #[Middleware(AuthMiddleware::class)]
    public function store(StatuesRequest $statuesRequest)
    {
        $payload = $statuesRequest->validated();
        $status = null;
        $account = Auth::account();
        $accountLimit = $this->redis->get("status:frequency:".$account['id']);

        if($accountLimit){
            throw new AppException("status.post_frequency_limit");
        }
        $this->redis->setex("status:frequency:".$account['id'],12,1);

        Db::transaction(function () use ($account, $payload, &$status) {
            $status = $this->statusesService->create($account['id'], $payload['content'], [
                'replyToId'        => $payload['reply_to_id'],
                'isSensitive'      => $payload['is_sensitive'],
                'commentsDisabled' => $payload['comments_disabled'] ?? 0,
                'whoCanReply'      => $payload['who_can_reply'] ?? 0,
                'scope'            => $payload['scope'] ?? null,
                'attachments'      => $payload['attachments'],
                'poll'             => $payload['poll'] ?? [],
                'enable_activitypub' => $payload['enable_activitypub'] ?? Status::ENABLE_ACTIVITYPUB_ON,
                'fee' => $payload['fee'] ?? null
            ]);
        });
        Queue::send(['id' => $status['id']], Queue::TOPIC_STATUS_CREATE);
        $statusId = $status['id'];

        return $this->show($statusId);
    }

    #[OA\Get(path: '/_api/v1/statuses/{acct}/status/{statusId}', summary: '推文详情', tags: ['推文'])]
    #[OA\Response(
        response: 200,
        description: ''
    )]
    public function show($statusId)
    {
        $status = $this->statusesService->detail(Auth::account(), $statusId);
        return StatusResource::make($status);
    }

    #[OA\Get(path: '/_api/v1/statuses/{acct}/status/{statusId}/replies', summary: '推文评论列表', tags: ['推文'])]
    #[OA\Response(
        response: 200,
        description: ''
    )]
    public function replies($acct, $statusId)
    {
        $account = Account::where('acct', $acct)->firstOrFail();
        $parentStatus = Status::findOrFail($statusId);
        $status = Status::withInfo(Auth::account())->where([
            ['reply_to_id', $statusId],
            ['reply_to_account_id', $account->id],
            ['scope', Status::SCOPE_PUBLIC]
        ]);

        if ($parentStatus->is_hidden_reply) {
            $status->where('is_hidden_reply', 1);
        } else {
            $status->where(function ($q) {
                $q->where('is_hidden_reply', 1)->orWhere('is_hidden_reply', 0);
            });
        }

        $statusList = $status->orderByDesc('id')->paginate(30);
        return StatusPaginateResource::make($statusList);
    }

    #[OA\Put(path: '/_api/v1/statuses/{statusId}/pin', summary: "推文 pin", tags: ['推文'])]
    #[OA\Response(
        response: 204,
        description: ''
    )]
    #[Middleware(AuthMiddleware::class)]
    public function pin($statusId)
    {
        $status = Status::findOrFail($statusId);
        $status->pinned_at = Carbon::now();
        $status->save();
        return $this->response->raw(null)->withStatus(204);
    }
    #[OA\Put(path: '/_api/v1/statuses/{statusId}/unpin', summary: "推文 unpin", tags: ['推文'])]
    #[OA\Response(
        response: 204,
        description: ''
    )]
    #[Middleware(AuthMiddleware::class)]
    public function unpin($statusId)
    {
        $status = Status::findOrFail($statusId);
        $status->pinned_at = null;
        $status->save();
        return $this->response->raw(null)->withStatus(204);
    }

    #[OA\Put(path: '/_api/v1/statuses/{statusId}/re-blog', summary: "转推", tags: ['推文'])]
    #[OA\Response(
        response: 204,
        description: ''
    )]
    #[Middleware(AuthMiddleware::class)]
    public function reBlog($statusId)
    {
        try {
            $this->statusesService->reBlog(Auth::account()['id'], $statusId);
        } catch (\Exception $e) {
            return $this->response->json(['msg' => $e->getMessage()])->withStatus(403);
        }
        return $this->response->raw(null)->withStatus(204);
    }
    #[OA\Put(path: '/_api/v1/statuses/{statusId}/undo-re-blog', summary: "取消转推", tags: ['推文'])]
    #[OA\Response(
        response: 204,
        description: ''
    )]
    #[Middleware(AuthMiddleware::class)]
    public function undoReBlog($statusId)
    {
        $this->statusesService->undoReBlog(Auth::account()['id'], $statusId);
        return $this->response->raw(null)->withStatus(204);
    }

    #[OA\Delete(path: "/_api/v1/statuses/{id}", summary: "删除推文", tags: ['推文'])]
    #[OA\Response(
        response: 204,
        description: ''
    )]
    #[Middleware(AuthMiddleware::class)]
    public function destroy($id)
    {
        try {
            $this->statusesService->destroy(Auth::account()['id'], $id);
        } catch (\Exception $e) {
            return $this->response->json(['msg' => $e->getMessage()])->withStatus(403);
        }
        return $this->response->raw(null)->withStatus(204);
    }

    #[OA\Put(path: "/_api/v1/statuses/{id}", summary: "编辑推文", tags: ['推文'])]
    #[OA\Parameter(name: 'content', description: '推文内容', in: 'query', required: true)]
    #[OA\Parameter(name: 'reply_to_id', description: '回复推文ID', in: 'query')]
    #[OA\Parameter(name: 'who_can_reply', description: '谁可以回复 0：所有人 1：仅关注的人 2：仅提及的人', in: 'query')]
    #[OA\Parameter(name: 'scope', description: '可见范围：1：公开 2：仅关注者可见 4：不公开', in: 'query')]
    #[OA\Parameter(name: 'poll', description: '投票选项,数组', in: 'query', example: '{"options":["options1","options2"],"expires_in":86400,"multiple":false}')]
    #[OA\Parameter(name: 'attachments', description: '附件数组', in: 'query', example: '')]
    #[OA\Parameter(name: 'is_sensitive', description: '是否敏感内容', in: 'query')]
    #[OA\Parameter(name: 'fee', description: '推文费用修改', in: 'query')]
    #[OA\Response(
        response: 204,
        description: ''
    )]
    #[Middleware(AuthMiddleware::class)]
    public function edit(StatuesRequest $statuesRequest, $id)
    {
        $payload = $statuesRequest->validated();
        $status = Status::findOrFail($id);
        if ($status->created_at->addHours()->lt(Carbon::now()) && empty($status->fee)) {
            return $this->response->json(['msg' => '请在发布一小时内编辑'])->withStatus(403);
        }

        if (StatusEdit::where('status_id', $status->id)->count() >= 10) {
            return $this->response->json(['msg' => 'You cannot edit your post more than 10 times.'])->withStatus(403);
        }

        $scopeMap = Status::SCOPE_MAP;
        $editPayload = [
            'status' => $payload['content'] ?? null,
            'media_ids' => !empty($payload['attachments']) ? array_column($payload['attachments'], 'id') : [],
            'poll' => $payload['poll'] ?? [],
            'in_reply_to_id' => $payload['reply_to_id'] ?? null,
            'sensitive' => $payload['is_sensitive'] ?? 0,
            'spoiler_text' => '',
            'visibility' => $scopeMap[$payload['scope']] ?? null,
            'fee' => $payload['fee'] ?? '0'
        ];
        $this->statusesService->edit($status, $editPayload);

        Queue::send(compact('id'), Queue::TOPIC_STATUS_UPDATE);
        return $this->show($id);
    }

    #[OA\Post(path: '/_api/v1/statuses/{statusId}/bookmark', summary: '添加标签', tags: ['推文'])]
    #[OA\Response(
        response: 200,
        description: '',
    )]
    #[Middleware(AuthMiddleware::class)]
    public function bookmark($statusId)
    {
        $this->statusesService->bookmark(Auth::account()['id'], $statusId);
        return $this->response->raw(null);
    }

    #[OA\Put(path: '/_api/v1/statuses/{statusId}/un-bookmark', summary: '删除标签', tags: ['推文'])]
    #[OA\Response(
        response: 200,
        description: '',
    )]
    #[Middleware(AuthMiddleware::class)]
    public function unBookmark($statusId)
    {
        $this->statusesService->unBookmark(Auth::account()['id'], $statusId);
        return $this->response->raw(null);
    }

    #[OA\Put(path: '/_api/v1/bookmarks/un-bookmark-all', summary: '清除所有标签', tags: ['推文'])]
    #[OA\Response(
        response: 200,
        description: '',
    )]
    #[Middleware(AuthMiddleware::class)]
    public function unBookmarkAll()
    {
        Bookmark::where('account_id', Auth::account()['id'])->get()->each(function ($item) {
            $item->delete();
        });
        return $this->response->raw(null);
    }

    #[OA\Get(path: '/_api/v1/bookmarks', summary: '获取标签列表', tags: ['推文'])]
    #[OA\Response(
        response: 200,
        description: ''
    )]
    #[Middleware(AuthMiddleware::class)]
    public function bookmarks()
    {
        $account = Auth::account();
        return Bookmark::with(['status' => function ($query) use ($account) {
            $query->withInfo($account);
        }])->where('account_id', $account['id'])->latest('id')->paginate(10);
    }

    #[OA\Post(path: '/_api/v1/polls/{pollId}/votes', summary: '投票', tags: ['推文'])]
    #[OA\Parameter(name: 'choices', description: '投票项，数组', in: 'query', required: true, example: '{"choices":["0"]}')]
    #[OA\Response(
        response: 200,
        description: ''
    )]
    #[Middleware(AuthMiddleware::class)]
    public function vote($pollId)
    {
        $choices = $this->request->input('choices');
        if (empty($choices) || !is_array($choices)) {
            return $this->response->json(['msg' => '必须选一个'])->withStatus(403);
        }
        $authAccountId = Auth::account()['id'];
        $poll = Poll::findOrFail($pollId);
        if (Carbon::now()->gt($poll->expires_at)) {
            return $this->response->json(['msg' => 'Poll expired.'])->withStatus(403);
        }

        if ($poll->multiple) {
            try {
                $this->statusesService->voteMultipleChoice($poll, $authAccountId, $choices);
            } catch (\Exception $e) {
                return $this->response->json(['msg' => $e->getMessage()])->withStatus(403);
            }
            return $this->show($poll->status_id);
        }

        try {
            $this->statusesService->voteSingleChoice($poll, $authAccountId, $choices[0]);
        } catch (\Exception $e) {
            return $this->response->json(['msg' => $e->getMessage()])->withStatus(403);
        }

        return $this->show($poll->status_id);
    }

    #[OA\Put(path: '/_api/v1/statuses/{statusId}/toggle', summary: '隐藏/显示 推文、评论', tags: ['推文'])]
    #[OA\Parameter(name: 'toggle', description: '切换隐藏/显示', in: 'query', required: true, example: '0,1')]
    #[OA\Response(
        response: 200,
        description: '',
    )]
    #[Middleware(AuthMiddleware::class)]
    public function toggle($statusId)
    {
        $toggle = $this->request->input('toggle');

        $status = Status::findOrFail($statusId);
        if (!in_array($toggle, [0, 1])) {
            return $this->response->json(['msg' => 'toggle not valid'])->withStatus(403);
        }

        if ($status->reply_to_account_id != Auth::account()['id']) {
            return $this->response->json(['msg' => '不能隐藏他人推文下的评论'])->withStatus(403);
        }

        $status->is_hidden_reply = $toggle;
        $status->save();
        return $this->response->raw(null);
    }

    #[OA\Get('/_api/v1/statuses/{statusId}/context', summary: '推文上下文', tags: ['推文'])]
    #[OA\Response(
        response: 200,
        description: '',
    )]
    public function context($statusId)
    {
        $account = Auth::account();
        [$ancestors, $descendants] = $this->statusesService->context($statusId, $account);
        return $this->response->json(compact('ancestors', 'descendants'));
    }

    #[OA\Post('/_api/v1/statuses/{statusId}/filter', summary: '过滤此推文', tags: ['推文'])]
    #[OA\Parameter(name: 'filter_id', description: '过滤器ID', in: 'query', required: true)]
    #[OA\Response(
        response: 200,
        description: 'filter 过滤器',
    )]
    public function filter($statusId)
    {
        $filterId = $this->request->input('filter_id');
        $account = Auth::account();
        UserFilter::updateOrCreate(['account_id' => $account['id'], 'filter_id' => $filterId, 'status_id' => $statusId]);
        return $this->response->raw(null);
    }

    #[OA\Put(path: '/_api/v1/statuses/{statusId}/mark-as-sensitive-account', summary: "将用户标记为敏感账号", tags: ['推文'])]
    #[OA\Response(
        response: 200,
        description: ''
    )]
    #[Middleware(AuthMiddleware::class)]
    public function markAsSensitiveAccount($statusId)
    {
        $status = Status::findOrFail($statusId);
        $account = $status->account;
        $account->sensitized_at = Carbon::now();
        $account->save();
        AccountWarning::create([
            'target_account_id' => $account->id,
            'action'            => AccountWarning::ACTION_SENSITIZED,
        ]);
        return $this->response->raw(null);
    }

    #[OA\Put(path: '/_api/v1/statuses/{statusId}/unlock', summary: "解锁", tags: ['推文'])]
    #[OA\Parameter(name: 'hash', description: '交易hash', in: 'body', required: true)]
    #[OA\Response(
        response: 200,
        description: ''
    )]
    #[Middleware(AuthMiddleware::class)]
    public function unlock($statusId)
    {
        $accountId = Auth::account()['id'];
        $hash = $this->request->input('hash');
        if (empty($hash)) {
            return $this->response->json(['msg' => '交易hash缺失'])->withStatus(403);
        }
        $status = Status::findOrFail($statusId);
        if ($status->fee <= 0) {
            return $this->response->json(['msg' => '不是付费内容'])->withStatus(403);
        }

        $unlockLog = $this->ethService->getUnlockLogByHash($hash);
        if (empty($unlockLog) || !$unlockLog instanceof UnlockLog) {
            return $this->response->json(['msg' => '解锁失败，请查看交易进度'])->withStatus(403);
        }

        $orderId = preg_replace('/[^0-9]/', '', $unlockLog->orderId);
        $payLog = PayLog::find($orderId);
        if (empty($payLog)) {
            return $this->response->json(['msg' => '订单未创建'])->withStatus(403);
        }

        if ($status->fee != $unlockLog->amount) {
            return $this->response->json(['msg' => '付费金额不一致'])->withStatus(403);
        }

        if ($statusId != $payLog->order_id) {
            return $this->response->json(['msg' => '付费推文不一致'])->withStatus(403);
        }

        if (strtolower($status->account->wallet_address) != $unlockLog->authorAddr) {
            return $this->response->json(['msg' => '付费目标钱包地址不是作者钱包地址'])->withStatus(403);
        }

        $timestamp = $this->ethService->getBlockTimestamp($unlockLog->block);
        $payLog = PayLog::updateOrCreate(['hash' => $hash], [
            'account_id' => $accountId,
            'target_account_id' => $status->account_id,
            'fee' => $unlockLog->amount,
            'send_addr' => $unlockLog->fromAddr,
            'recv_addr' => $unlockLog->authorAddr,
            'state' => PayLog::STATE_SUCCESS,
            'type' => PayLog::TYPE_UNLOCK_STATUS,
            'order_id' => $statusId,
            'block' => $unlockLog->block,
            'paid_at' => Carbon::createFromTimestamp($timestamp)
        ]);
        StatusUnlockLog::firstOrCreate(
            ['account_id' => $accountId, 'status_id' => $statusId],
            ['state' => StatusUnlockLog::STATE_UNLOCKED_Y, 'target_account_id' => $status->account_id, 'fee' => $unlockLog->amount, 'pay_log_id' => $payLog->id]
        );

        return $this->show($statusId);
    }
    #[OA\Post(path: '/_api/v1/view-statuses', summary: "查看推文记录", tags: ['推文'])]
    #[OA\Parameter(name: 'status_ids', description: '阅读的推文IDs , 分割', in: 'body', required: true)]
    #[OA\Response(
        response: 200,
        description: ''
    )]
    public function views()
    {
        $ids = $this->request->input('status_ids');
        $ids = explode(',', $ids);
        $this->statusesService->viewStatuses($ids);
        return $this->response->raw(null)->withStatus(204);
    }
}
