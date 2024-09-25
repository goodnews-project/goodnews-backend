<?php

namespace App\Resource\Mastodon;

use App\Model\Notification;
use Carbon\Carbon;
use Hyperf\Resource\Json\JsonResource;

class NotificationResource extends JsonResource
{
    const TYPE_MAP = [
        Notification::NOTIFY_TYPE_MENTION        => 'mention' ,
        Notification::NOTIFY_TYPE_STATUS         => 'status',
        Notification::NOTIFY_TYPE_REBLOG         => 'reblog',
        Notification::NOTIFY_TYPE_FOLLOW         => 'follow',
        Notification::NOTIFY_TYPE_FOLLOW_REQUEST => 'follow_request',
        Notification::NOTIFY_TYPE_FAVOURITE      => 'favourite',
        Notification::NOTIFY_TYPE_POLL           => 'poll',
        Notification::NOTIFY_TYPE_UPDATE         => 'update',
        Notification::NOTIFY_TYPE_ADMIN_SIGN_UP  => 'admin.sign_up',
        Notification::NOTIFY_TYPE_REPORT         => 'admin.report',
    ];
    public ?string $wrap = null;
    /**
     * Transform the resource into an array.
     * https://docs.joinmastodon.org/entities/Notification/
     * @return array
     */
    public function toArray(): array
    {
        $notification = $this->resource;
        return [
            'id'                            => (string)$notification->id,
            'type'                          => (string) (self::TYPE_MAP[$notification->notify_type]?? 'status'),
            'created_at'                    => $notification->created_at?->toIso8601ZuluString('m') ?: Carbon::now()->toIso8601ZuluString('m'),
            'account'                       => $notification->account ? AccountResource::make($notification->account) : null,
            'status'                        => $notification->status ? StatusResource::make($notification->status) : null,
            'report'                        => null,
            'relationship_severance_event ' => null,
        ];
    }
}
