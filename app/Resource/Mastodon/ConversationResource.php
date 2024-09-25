<?php

namespace App\Resource\Mastodon;

use App\Model\Conversation;
use App\Model\DirectMessage;
use Carbon\Carbon;
use Hyperf\Resource\Json\JsonResource;

class ConversationResource extends JsonResource
{
    public ?string $wrap = null;
    /**
     * Transform the resource into an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        if (!$this->resource instanceof DirectMessage) {
            return [];
        }

        $dm = $this->resource;
        $currAccountId = $dm?->currAccountId;
        $conversation = Conversation::where('dm_id', $dm->id)->firstOrFail();

        // todo To setLocale by account language
        Carbon::setLocale('zh-CN');
        return [
            'id' => (string) $dm->id,
            'cid' => (string) $conversation->id ?? '',
            'isAuthor' => (string) $currAccountId == $dm->from_id,
            'type' => $dm->dm_type,
            'media' => null,
            'unread' => is_null($dm->read_at),
            'accounts' => [AccountResource::make($dm->author)],
            'last_status' => StatusResource::make($dm->status),
            'timeAgo' => $dm->created_at->diffForHumans(null, null, true),
            'time' => $dm->created_at->getTimestamp()
        ];

    }
}
