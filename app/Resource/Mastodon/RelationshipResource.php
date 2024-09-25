<?php

namespace App\Resource\Mastodon;

use App\Model\Block;
use App\Model\Follow;
use App\Model\FollowRequest;
use App\Model\Mute;
use Hyperf\Resource\Json\JsonResource;

class RelationshipResource extends JsonResource
{
    public ?string $wrap = null;
    /**
     * Transform the resource into an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        $id = $this->resource['id'];
        $accountId = $this->resource['accountId'];
        $muted = Mute::where('account_id', $accountId)->where('target_account_id', $id)->exists();
        $followedBy = Follow::where('account_id', $id)->where('target_account_id', $accountId)->exists();
        return [
            'id' => (string) $id,
            'following' => Follow::where('account_id', $accountId)->where('target_account_id', $id)->exists(),
            'followed_by' => $followedBy,
            'showing_reblogs' => true,
            'notifying' => $muted,
            'blocking' => Block::where('account_id', $accountId)->where('target_account_id', $id)->exists(),
            'blocked_by' => Block::where('account_id', $id)->where('target_account_id', $accountId)->exists(),
            'muting' => $muted,
            'muting_notifications' => $muted,
            'requested' => FollowRequest::where('account_id', $accountId)->where('target_account_id', $id)->exists(),
            'domain_blocking' => false,
            'endorsed' => false,
        ];
    }
}
