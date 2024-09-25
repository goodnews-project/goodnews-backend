<?php

declare(strict_types=1);

namespace App\Controller;

use App\Exception\AppException;
use App\Middleware\AuthMiddleware;
use App\Model\Account;
use App\Model\AccountSubscriberLog;
use App\Model\Follow;
use App\Model\Notification;
use App\Model\PayLog;
use App\Model\Status;
use App\Model\User;
use App\Nsq\Consumer\FollowConsumer;
use App\Nsq\Queue;
use App\Request\SubscribeRequest;
use App\Service\AccountService;
use App\Service\Auth;
use App\Service\SubscribeLog;
use App\Service\UserService;
use App\Service\Web3Service;
use Carbon\Carbon;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\Swagger\Annotation as OA;
use Richard\HyperfPassport\PassportAuthMiddleware;
use function Hyperf\Config\config;
use function Hyperf\Support\env;
use function Hyperf\Translation\trans;

#[OA\HyperfServer('http')]
class AccountController extends AbstractController
{

    #[Inject]
    protected AccountService $accountService;


    #[OA\Get(path:'/_api/v1/accounts/verify_credentials',
        description: 'reference：https://docs.joinmastodon.org/methods/accounts/#verify_credentials',
        summary:'用token获取用户信息',tags:['account'])]
    #[Middleware(PassportAuthMiddleware::class)]
    public function verifyCredentials()
    {
        $user = Auth::passportUser();
        $account = $user->account;
        if (empty($user->confirmed_at)) {
            return $this->response->json(['msg' => trans('message.account.login_is_confirmed_email')])->withStatus(403);
        }

        $this->accountService->afterVerifyCredentials($user);
        return $account;
    }

    #[OA\Get("/_api/v1/account/{acct}", summary: 'account详情', tags: ['account'])]
    #[OA\Response(
        response: 200,
        description: ''
    )]
    public function show(string $acct)
    {
        $loginAccount = Auth::account();
        return $this->accountService->details($acct, $loginAccount);
    }

    #[OA\Put('/_api/v1/follow', summary: "关注用户", tags: ['account'])]
    #[OA\Parameter(name: 'account_id', description: '被关注的用户 account_id', in: 'body', required: true, schema: new OA\Schema(type: 'string'))]
    #[Middleware(AuthMiddleware::class)]
    #[OA\Response(
        response: 204,
        description: '操作成功'
    )]
    public function follow()
    {
        $accountId = $this->request->input('account_id');
        $authAccount = Auth::account();
        $this->accountService->follow($accountId, $authAccount['id']);
        return $this->response->raw(null)->withStatus(204);
    }
    #[OA\Put('/_api/v1/{acct}/status-notify', summary: "新推文通知开关", tags: ['account'])]
    #[OA\Parameter(name: 'enable', description: '0:关闭 1:开启', in: 'body', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Response(
        response: 204,
        description: '操作成功'
    )]
    #[Middleware(AuthMiddleware::class)]
    public function followNotify($acct)
    {
        $enable = (int)$this->request->input('enable', 1);
        $authAccount = Auth::account();

        $account = Account::where('acct', $acct)->firstOrFail();
        $follow = Follow::where([
            ['account_id', $authAccount['id']],
            ['target_account_id', $account->id]
        ])->first();

        if (!$follow) {
            $this->response->json(['msg' => '请先关注用户'])->withStatus(403);
        }

        if ($enable) {
            $follow->update(['notify' => Follow::NOTIFY_ENABLE]);
        } else {
            $follow->update(['notify' => Follow::NOTIFY_DISABLE]);
        }

        return $this->response->raw(null)->withStatus(204);
    }

    #[OA\Put('/_api/v1/un-follow', summary: "取消关注用户", tags: ['account'])]
    #[OA\Parameter(name: 'account_id', description: '被取消关注的用户 account_id', in: 'body', required: true, schema: new OA\Schema(type: 'string'))]
    #[Middleware(AuthMiddleware::class)]
    #[OA\Response(
        response: 204,
        description: '操作成功'
    )]
    public function unFollow()
    {
        $accountId = $this->request->input('account_id');
        $authAccountId = Auth::account()['id'];
        $this->accountService->unFollow($accountId, $authAccountId);

        return $this->response->raw(null)->withStatus(204);
    }

    #[OA\Get("/_api/v1/account/{acct}/follower", summary: 'account 关注者', tags: ['account'])]
    #[OA\Parameter(name: 'keyword', description: '检索', in: 'body', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Response(
        response: 200,
        description: ''
    )]
    public function follower(string $acct)
    {
        $keyword = $this->request->input('keyword');
        $account = Account::where('acct', $acct)->firstOrFail();
        return Account::isFollow(Auth::account())
            ->whereHas('follows', fn($q) => $q->where('target_account_id', $account['id']))
            ->orderByDesc('id')
            ->when(
                $keyword,
                fn($query) => $query->where('username', 'like', "%{$keyword}%")
                    ->orWhere('acct', 'like', "%{$keyword}%")
            )
            ->paginate();
    }
    #[OA\Get("/_api/v1/account/{acct}/following", summary: 'account 正在关注', tags: ['account'])]
    #[OA\Response(
        response: 200,
        description: ''
    )]
    public function following(string $acct)
    {
        $account = Account::where('acct', $acct)->isFollow(Auth::account())->firstOrFail();
        return Account::isFollow(Auth::account())
            ->whereHas('followers', fn($q) => $q->where('account_id', $account['id']))
            ->orderByDesc('id')
            ->paginate();
    }

    #[OA\Get("/_api/v1/follow_requests", summary: '关注请求列表', tags: ['account'])]
    #[OA\Response(
        response: 200,
        description: ''
    )]
    #[Middleware(AuthMiddleware::class)]
    public function followRequests()
    {
        $account = Account::findOrFail(Auth::account()['id']);
        $limit = $this->request->input('limit', 40);
        return $this->accountService->followRequests($account, $limit);
    }

    #[OA\Post("/_api/v1/follow_requests/{id}/authorize", summary: '关注请求-批准', tags: ['account'])]
    #[OA\Response(
        response: 200,
        description: ''
    )]
    #[Middleware(AuthMiddleware::class)]
    public function followRequestAccept($id)
    {
        $account = Account::findOrFail(Auth::account()['id']);
        $this->accountService->followRequestAccept($account, $id);
        return $this->response->raw(null);
    }

    #[OA\Post("/_api/v1/follow_requests/{id}/reject", summary: '关注请求-拒绝', tags: ['account'])]
    #[OA\Response(
        response: 200,
        description: ''
    )]
    #[Middleware(AuthMiddleware::class)]
    public function followRequestReject($id)
    {
        $account = Account::findOrFail(Auth::account()['id']);
        $this->accountService->followRequestReject($account, $id);

        return $this->response->raw(null);
    }

    #[OA\Get("/_api/v1/account/follow/recommendation", summary: '推荐关注', tags: ['account'])]
    #[OA\Response(
        response: 200,
        description: ''
    )]
    #[Middleware(AuthMiddleware::class)]
    public function suggestions()
    {
        $accountId = Auth::account()['id'];
        $account = Account::findOrFail($accountId);
        return $this->accountService->getSuggestions($account, 4);
    }

    #[OA\Post("/_api/v1/account/{id}/subscribe", summary: '订阅', tags: ['account'])]
    #[OA\Parameter(name: 'plan_id', description: '计划ID', in: 'body', required: true)]
    #[OA\Parameter(name: 'hash', description: '交易hash', in: 'body', required: true)]
    #[OA\Response(
        response: 200,
        description: ''
    )]
    #[Middleware(AuthMiddleware::class)]
    public function subscribe($id, SubscribeRequest $subscribeRequest)
    {
        $payload = $subscribeRequest->validated();
        $accountId = Auth::account()['id'];
        $account = Account::findOrFail($accountId);
        $targetAccount = Account::findOrFail($id);

        if ($targetAccount->fee <= 0) {
            throw new AppException("subscribe_error.no_subscribe");
        }

        $hash = $payload['hash'];
        $planId = $payload['plan_id'] ?? null;

        if ($planId && empty($targetAccount->subscriber_plan)) {
            throw new AppException("subscribe_error.no_subscribe_plan");
        }

        $subLog = $this->ethService->getSubLog($hash);
        if (empty($subLog) || !$subLog instanceof SubscribeLog) {
            throw new AppException("subscribe_error.pay_failed");
        }

        $subLogOrderId = preg_replace('/[^0-9]/', '', $subLog->orderId);
        $payLog = PayLog::find($subLogOrderId);
        if (empty($payLog)) {
            return $this->response->json(['msg' => '订单未创建'])->withStatus(403);
        }

        if ($payLog->target_account_id != $id) {
            throw new AppException('支付对象不匹配');
        }

        if (Carbon::createFromTimestamp($subLog->timestamp)->addMinutes(10)->lt(Carbon::now())) {
            throw new AppException("subscribe_error.pay_timeout");
        }

        $fee = $targetAccount->fee;
        $plan_discount = null;
        $plan_term = null;
        $expired_at = Carbon::createFromTimestamp($subLog->timestamp)->addMonths();

        if ($planId > 0 && $targetAccount->subscriber_plan) {
            $subPlanMap = \Hyperf\Collection\collect($targetAccount->subscriber_plan)->keyBy('id')->toArray();
            if (empty($subPlanMap[$planId])) {
                throw new AppException("subscribe_error.unknown_plan");
            }

            $plan = $subPlanMap[$planId];
            $fee = $plan['plan_fee'];
            $plan_discount = $plan['plan_discount'];
            $plan_term = $plan['plan_term'];
            $expired_at = Carbon::createFromTimestamp($subLog->timestamp)->addMonths(intval($plan_term));

            if ($planId != $payLog->order_id) {
                throw new AppException("subscribe_error.plan_id_not_match");
            }
        }

        if ($subLog->amount != $fee) {
                throw new AppException("subscribe_error.pay_amount_not_match");
        }

        if (strtolower($targetAccount->wallet_address) != $subLog->authorAddr) {
                throw new AppException("subscribe_error.pay_address_not_match");
        }

        $payLog = PayLog::updateOrCreate(['hash' => $hash], ['account_id' => $accountId, 'target_account_id' => $targetAccount->id, 'fee' => $fee, 'send_addr' => $subLog->fromAddr, 'recv_addr' =>
        $subLog->authorAddr, 'type' => PayLog::TYPE_SUBSCRIBE_ACCOUNT, 'order_id' => $planId, 'state' => PayLog::STATE_SUCCESS, 'block' => $subLog->block, 'paid_at' => Carbon::createFromTimestamp($subLog->timestamp)]);
        $existsSubscribeLog = AccountSubscriberLog::where('account_id', $account->id)
            ->where('target_account_id', $targetAccount->id)
            ->where('expired_at', '>', Carbon::now())
            ->first();
        if (!$existsSubscribeLog) {
            AccountSubscriberLog::create(
                [
                    'account_id' => $account->id,
                    'target_account_id' => $targetAccount->id,
                    'state' => AccountSubscriberLog::STATE_SUBSCRIBED,
                    'pay_log_id' => $payLog->id,
                    'fee' => $fee,
                    'plan_id' => $planId,
                    'expired_at' => $expired_at,
                    'plan_discount' => $plan_discount,
                    'plan_term' => $plan_term
                ]
            );
        }

        return $this->response->raw(null)->withStatus(204);
    }

    #[OA\Put("/_api/v1/account/{id}/unsubscribe", summary: '取消订阅(Deprecated)', tags: ['account'])]
    #[OA\Response(
        response: 200,
        description: ''
    )]
    #[Middleware(AuthMiddleware::class)]
    public function unSubscribe($id)
    {
        $accountId = Auth::account()['id'];
        $existsSubscribeLog = AccountSubscriberLog::where('account_id', $accountId)
            ->where('target_account_id', $id)
            ->where('expired_at', '>', Carbon::now())
            ->first();
        if (!$existsSubscribeLog) {
                throw new AppException("unsubscribe_error.not_found_record");
        }

        $existsSubscribeLog->state = AccountSubscriberLog::STATE_UNSUBSCRIBED;
        $existsSubscribeLog->save();
        return $this->response->raw(null)->withStatus(204);
    }
}
