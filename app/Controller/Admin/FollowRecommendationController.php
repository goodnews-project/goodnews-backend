<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\AbstractController;
use App\Model\FollowRecommendation;
use App\Request\FollowRecommendationRequest;
use Hyperf\Swagger\Annotation as OA;

#[OA\HyperfServer('http')]
class FollowRecommendationController extends AbstractController
{

    #[OA\Get('/admin/_api/follow_recommendations', summary:"推荐关注列表", tags:["admin", "推荐关注"])]
    #[OA\Parameter(name: 'status', description: '1 活跃 2 禁用', in : 'query')]
    #[OA\Parameter(name: 'language', description: '选择语言', in : 'query')]
    public function index(FollowRecommendationRequest $followRecommendationRequest)
    {
        $payload = $followRecommendationRequest->validated();
        $status = $payload['status'] ?? FollowRecommendation::STATUS_UNSUPPRESSED;
        $q = FollowRecommendation::with(['account:id,acct,display_name,avatar']);
        if (!empty($payload['language'])) {
            $q->where('language', $payload['language']);
        }
        return $q->where('status', $status)->latest('rank')->get();
    }

    #[OA\Put('/admin/_api/follow_recommendations/suppressed', summary:"推荐关注禁用/恢复", tags:["admin", "推荐关注"])]
    #[OA\Parameter(name: 'account_ids', description: '账号ID数组，全选未勾选时传', in : 'query')]
    #[OA\Parameter(name: 'status', description: '1 活跃 2 禁用', in : 'query')]
    #[OA\Parameter(name: 'language', description: '选择语言', in : 'query')]
    #[OA\Parameter(name: 'batch_checkbox_all', description: '全选否选 1 勾选 0未勾选', in : 'query')]
    public function suppressed(FollowRecommendationRequest $followRecommendationRequest)
    {
        $payload = $followRecommendationRequest->validated();
        $accountIds = $payload['account_ids'] ?? [];
        $status = $payload['status'] ?? FollowRecommendation::STATUS_UNSUPPRESSED;
        if (!empty($payload['batch_checkbox_all'])) {
            $frs = $this->index($followRecommendationRequest);
            foreach ($frs as $v) {
                $v->update(['status' => $status]);
            }
            return $this->response->raw(null);
        }

        foreach ($accountIds as $accountId) {
            FollowRecommendation::where('account_id', $accountId)->update(['status' => $status]);
        }
        return $this->response->raw(null);
    }


}
