<?php

namespace App\Resource;

use App\Model\AccountInstanceBlock;
use App\Model\Status;
use App\Service\Auth;
use App\Service\StatusCacheService;
use Hyperf\Paginator\LengthAwarePaginator;
use function Hyperf\Support\env;

trait Process
{
    public function fillStatusAttr(Status $status)
    {
        $statusCacheService = new StatusCacheService();
        $statusCache = $statusCacheService->getStatusById($status->id);
        return $status->fill($statusCache->getAttributes());
    }

    public function setIsBlockedInstance(Status $status)
    {
        $status->is_blocked_instance = false;
        if ($account = Auth::account()) {
            $domain = $status->account->domain ?: env('AP_HOST');
            $status->is_blocked_instance = AccountInstanceBlock::where('account_id', $account['id'])->where('domain', $domain)->exists();
        }
    }

    public function setUnlockAttachmentsByStatus(Status $status)
    {
        // 付费内容未解锁，也未订阅作者
        $authAccountId = Auth::account()['id'] ?? null;
        if ($authAccountId <= 0 && $status->fee > 0 && $status->attachments->isNotEmpty()) {
            $status->attachments->transform(fn ($item) => $item->only(['blurhash', 'width', 'height', 'thumbnail_height', 'thumbnail_width']));
            return;
        }

        if ($authAccountId > 0 && $authAccountId != $status->account_id && $status->attachments->isNotEmpty() && $status->fee > 0 && empty($status->unlockLog) && empty($status->subscriberUnlockLog)) {
            $status->attachments->transform(fn ($item) => $item->only(['blurhash', 'width', 'height', 'thumbnail_height', 'thumbnail_width']));
        }
    }

    public function setUnlockAttachmentsByStatusPaginator(LengthAwarePaginator $lengthAwarePaginator)
    {
        foreach ($lengthAwarePaginator as $item) {
            $filledStatus = $this->fillStatusAttr($item);
            $this->setIsBlockedInstance($filledStatus);
            $this->setUnlockAttachmentsByStatus($filledStatus);
        }
    }
}