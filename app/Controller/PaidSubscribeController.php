<?php

declare(strict_types=1);

namespace App\Controller;
use App\Middleware\AuthMiddleware;
use App\Model\Account;
use App\Model\AccountSubscriberLog;
use App\Model\Status;
use App\Model\StatusUnlockLog;
use App\Resource\StatusPaginateResource;
use App\Service\Auth;
use Carbon\Carbon;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\Swagger\Annotation as OA;

#[OA\HyperfServer('http')]
#[Middleware(AuthMiddleware::class)]
class PaidSubscribeController extends AbstractController
{

    #[OA\Get('/_api/subscribe/account', summary:"订阅账号列表", tags:["_api", "paid-subscribe"])]
    #[OA\Parameter(name: 'keyword', description: '搜索用户名', in: 'body', required: false)]
    #[OA\Parameter(name: 'is_hidden', description: '是否隐藏 1 隐藏 ', in: 'body', required: false)]
    #[OA\Response(
        response: 200,
        description: 'display_name:用户名； is_subscribed:是否已订阅；username：用户名显示加@username，state:0（未订阅时） 1 订阅中 3 已过期；unlock_num：已解锁数量；unread_num：未读数量'
    )]
    public function index()
    {
        $accountId = Auth::account()['id'];
        $kw = $this->request->input('keyword');
        $isHidden = $this->request->input('is_hidden');

        $accounts = [];

        // 订阅的账号
        AccountSubscriberLog::with('targetAccount')
            ->where('account_id', $accountId)
            ->get()
            ->each(function (AccountSubscriberLog $log) use (&$accounts, $isHidden) {
                if (!empty($accounts[$log->target_account_id]) && $accounts[$log->target_account_id]['state'] == AccountSubscriberLog::STATE_SUBSCRIBED) {
                    return;
                }

                if ($isHidden && $log->real_state == AccountSubscriberLog::STATE_SUBSCRIBE_EXPIRED) {
                    return;
                }

                $accounts[$log->target_account_id] = [
                    'is_subscribed' => true,
                    'display_name' => $log->targetAccount->display_name,
                    'username' => $log->targetAccount->username,
                    'avatar' => $log->targetAccount->avatar,
                    'acct' => $log->targetAccount->acct,
                    'id' => $log->targetAccount->id,
                    'state' => $log->real_state,
                    'unlock_num' => 0,
                    'unread_num' => $log->unread_num
                ];
            });

        // 未订阅，只解锁的账号
        StatusUnlockLog::with('targetAccount')
            ->where('account_id', $accountId)
            ->whereNotNull('target_account_id')
            ->groupBy(['target_account_id'])
            ->selectRaw('target_account_id, count(1) unlock_num')
            ->get()
            ->each(function (StatusUnlockLog $log) use (&$accounts) {
                $unlockNum = $log->unlock_num ?? 0;
                if (!empty($accounts[$log->target_account_id])) {
                    $accounts[$log->target_account_id]['unlock_num'] = $unlockNum;
                } else {
                    $accounts[$log->target_account_id] = [
                        'is_subscribed' => false,
                        'display_name' => $log->targetAccount->display_name,
                        'username' => $log->targetAccount->username,
                        'avatar' => $log->targetAccount->avatar,
                        'acct' => $log->targetAccount->acct,
                        'id' => $log->targetAccount->id,
                        'state' => 0,
                        'unlock_num' => $unlockNum
                    ];
                }
            });

        $results = array_values($accounts);
        if ($kw) {
            foreach ($results as $k => $result) {
                if (!str_contains($result['display_name'], $kw) || !str_contains($result['username'], $kw)) {
                    unset($results[$k]);
                }
            }
        }
        return $results;
    }

    #[OA\Get('/_api/subscribe/account/{id}/status', summary:"解锁账号推文", tags:["_api", "paid-subscribe"])]
    #[OA\Response(
        response: 200,
        description: '返回空数组时即订阅已过期&&没有单独购买的推文'
    )]
    public function status($id)
    {
        $account = Auth::account();

        // 设置已读
        AccountSubscriberLog::where('account_id', $account['id'])->where('target_account_id', $id)->update(['unread_num' => 0]);

        // 已订阅
        if ($subLog = AccountSubscriberLog::where('account_id', $account['id'])
            ->where('target_account_id', $id)
            ->where('expired_at', '>', Carbon::now())
            ->first()) {
            $statusList = Status::withInfo($account, true)
                ->where('account_id', $id)
                ->where('fee', '>', 0)
                ->where(function ($q) use ($subLog) {
                    $q->where(function ($q) use ($subLog) {
                        $paidAt = $subLog->payLog?->paid_at ?: ($subLog->created_at ?: $subLog->updated_at);
                        $q->whereNotNull('deleted_at')->where('deleted_at', '>=', $paidAt);
                    })->orWhereNull('deleted_at');
                })
                ->orderByDesc('id')
                ->paginate(30);
            return StatusPaginateResource::make($statusList);
        }

        // 未订阅，查询解锁推文
        $statusIds = StatusUnlockLog::where('account_id', $account['id'])
            ->where('state', StatusUnlockLog::STATE_UNLOCKED_Y)
            ->pluck('status_id');
        if ($statusIds->isNotEmpty()) {
            $statusList = Status::withInfo($account, true)
                ->whereIn('id', $statusIds)
                ->where('account_id', $id)
                ->orderByDesc('id')
                ->paginate(30);
            return StatusPaginateResource::make($statusList);
        }

        return [];
    }

    #[OA\Put('/_api/subscribe/account/read-all', summary:"全部已读", tags:["_api", "paid-subscribe"])]
    #[OA\Response(
        response: 200,
        description: ''
    )]
    public function readAll()
    {
        $accountId = Auth::account()['id'];
        AccountSubscriberLog::where('account_id', $accountId)->update(['unread_num' => 0]);
        return $this->response->raw(null)->withStatus(204);
    }
}
