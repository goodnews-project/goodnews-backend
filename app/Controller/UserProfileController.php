<?php

declare(strict_types=1);

namespace App\Controller;

use App\Middleware\AuthMiddleware;
use App\Model\Account;
use App\Model\Attachment;
//use App\Model\WalletAddressLog;
use App\Request\UserProfileRequest;
use App\Service\AccountService;
use App\Service\Auth;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\Swagger\Annotation as OA;

#[OA\HyperfServer('http')]
class UserProfileController extends AbstractController
{
    #[Inject]
    protected AccountService $accountService;

    #[OA\Put("/_api/v1/user-profile",summary:"用户资料编辑",tags:['用户资料'])]
    #[OA\Parameter(name: 'avatar_attachment_id', description: '用户名头像 附件id,不改不传', in : 'body', required: false)]
    #[OA\Parameter(name: 'profile_image_attachment_id', description: '用户背景,暂定字段将来可能会改 附件id,不改不传', in : 'body', required: false)]
    #[OA\Parameter(name: 'display_name', description: '用户名称', in : 'body', required: false)]
    #[OA\Parameter(name: 'note', description: '用户详情,不改不传', in : 'body', required: false)]
    #[OA\Parameter(name: 'manually_approves_follower', description: '手动审核关注请求', in : 'body', required: false)]
    #[OA\Parameter(name: 'enable_activitypub', description: '锁嘟', in : 'body', required: false)]
    #[OA\Parameter(name: 'is_bot', description: '设置为机器人', in : 'body', required: false)]
    #[OA\Parameter(name: 'is_sensitive', description: '将发布的内容标记为敏感，1 标记 0 不标记 默认0', in : 'body', required: false)]
    #[OA\Parameter(name: 'is_display_sensitive', description: '显示可能包含敏感的内容，1 显示 0 不显示， 默认0', in : 'body', required: false)]
    #[OA\Parameter(name: 'fields', description: '标签内容', in : 'body', required: false, example: '[{"name":"Official","type":"PropertyValue","value":"https://x.com/xiaxiaoqiang"},{"name":"Contact Us","type":"PropertyValue","value":"https://good.news/about"}]')]
    #[OA\Parameter(name: 'fee', description: '允许付费订阅的价格，不允许为0', in : 'body', required: false)]
    #[OA\Parameter(name: 'is_long_term', description: '是否长期订阅', in : 'body', required: false)]
    #[OA\Parameter(name: 'wallet_address', description: '钱包地址', in : 'body', required: false)]
    #[OA\Response(
        response: 201,
        description: '操作成功'
    )]
    #[Middleware(AuthMiddleware::class)]
    public function store(UserProfileRequest $userProfileRequest)
    {
        $payload = $userProfileRequest->validated();
        $account = Account::where('acct',Auth::account()['acct'])->firstOrFail();

        if(!empty($payload['avatar_attachment_id'])){
            $payload['avatar'] = Attachment::where('id',$payload['avatar_attachment_id'])->value('url');
        }

        if(!empty($payload['profile_image_attachment_id'])){
            $payload['profile_image'] = Attachment::where('id',$payload['profile_image_attachment_id'])->value('url');
        }

        if (!empty($payload['is_bot'])) {
            $payload['actor_type'] = Account::ACTOR_TYPE_SERVICE;
        } else {
            $payload['actor_type'] = $account->actor_type == Account::ACTOR_TYPE_SERVICE ? Account::ACTOR_TYPE_PERSON :  $account->actor_type;
        }

        if (empty($payload['note'])) {
            $payload['note'] = null;
        } else {
            $payload['note'] = handleStatusContent($payload['note']);
        }

        if (isset($payload['fields'])) {
            $payload['fields'] = array_filter((array) $payload['fields']);
            if (empty($payload['fields'])) {
                $payload['fields'] = null;
            }
        }

        if (isset($payload['fee'])) {
            if ($payload['fee'] <= 0) {
                $payload['fee'] = null;
                $payload['subscriber_plan'] = null;
            } else {
                $payload['subscriber_plan'] = $payload['is_long_term'] == 1 ? $this->accountService->getDefaultSubscriberPlan($payload['fee']) : $this->accountService->getDefaultSubscriberPlan($payload['fee'], 0);
            }
        }

        if (isset($payload['wallet_address'])) {
            $payload = $this->changeWalletAddress($account['id'], $payload);
        }

        unset($payload['avatar_attachment_id'],$payload['profile_image_attachment_id'],$payload['is_bot'],$payload['is_long_term']);

        $account->update($payload);
        return $this->response->raw(null)->withStatus(201);
    }

    public function changeWalletAddress($accountId, $payload)
    {
        $walletAddress = $payload['wallet_address'] ?? null;
        if (empty($walletAddress)) {
            $payload['wallet_address'] = null;
            return $payload;
        }
//        $walletAddressLog = WalletAddressLog::where('address', $walletAddress)->first();
//        if (empty($walletAddressLog)) {
//            WalletAddressLog::create([
//                'account_id' => $accountId,
//                'address' => $walletAddress,
//            ]);
//        }
        $payload['wallet_address'] = $walletAddress;
        return $payload;
    }
}
