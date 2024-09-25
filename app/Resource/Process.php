<?php

namespace App\Resource;

use App\Model\Status;
use App\Service\Auth;
use Hyperf\Paginator\LengthAwarePaginator;

trait Process
{
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
            $this->setUnlockAttachmentsByStatus($item);
        }
    }
}