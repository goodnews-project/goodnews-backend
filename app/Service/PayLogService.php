<?php

namespace App\Service;

use App\Exception\AppException;
use App\Model\Account;
use App\Model\Block;
use App\Model\PayLog;
use App\Model\Status;
use Carbon\Carbon;

class PayLogService
{
    public function create($authAccountId, array $payload)
    {
        $type = $payload['type'] ?? null;
        $rewardType = $payload['reward_type'] ?? null;
        $amount = $payload['amount'] ?? null;
        $targetAccountId = $payload['target_account_id'] ?? null;
        $statusId = $payload['status_id'] ?? null;
        $planId = $payload['plan_id'] ?? null;
        $orderId = null;
        if ($type == PayLog::TYPE_REWARD) {
            $map = $this->getTargetAccountIdAndOrderIdViaRewardType($rewardType, $targetAccountId, $statusId);
            $targetAccountId = $map['targetAccountId'];
            $orderId = $map['orderId'];
        } elseif ($type == PayLog::TYPE_UNLOCK_STATUS) {
            $map = $this->getTargetAccountIdAndOrderIdViaStatusId($statusId);
            $targetAccountId = $map['targetAccountId'];
            $orderId = $map['orderId'];
        } elseif ($type == PayLog::TYPE_SUBSCRIBE_ACCOUNT) {
            if ($planId <= 0) {
                throw new AppException('pay_log.plan_id_is_empty');
            }

            $orderId = $planId;
        }

        return PayLog::create([
            'hash' => uniqid('default_hash_'),
            'send_addr' => uniqid('default_send_addr_'),
            'recv_addr' => uniqid('default_recv_addr_'),
            'block' => uniqid('default_block_'),
            'paid_at' => Carbon::now(),
            'account_id' => $authAccountId,
            'target_account_id' => $targetAccountId,
            'order_id' => $orderId,
            'fee' => $amount,
            'state' => PayLog::STATE_PENDING,
            'type' => $type,
            'reward_type' => $rewardType,
        ]);
    }

    private function getTargetAccountIdAndOrderIdViaStatusId($statusId): array
    {
        $status = Status::find($statusId);
        $targetAccountId = $status->account_id;
        $orderId = $statusId;
        return compact('targetAccountId', 'orderId');
    }

    private function getTargetAccountIdAndOrderIdViaRewardType($rewardType, $targetAccountId, $statusId): array
    {
        if ($rewardType <= 0) {
            throw new AppException('pay_log.reward_type_miss');
        }

        if ($rewardType == PayLog::REWARD_TYPE_STATUS) {
            return $this->getTargetAccountIdAndOrderIdViaStatusId($statusId);
        }

        $orderId = $targetAccountId;

        return compact('targetAccountId', 'orderId');
    }
}
