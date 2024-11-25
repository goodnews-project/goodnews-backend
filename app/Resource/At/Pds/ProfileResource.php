<?php

namespace App\Resource\At\Pds;

use App\Model\Account;
use Hyperf\Resource\Json\JsonResource;

class ProfileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        if (!$this->resource instanceof Account) {
            return [];
        }
        $account = $this->resource;

        return [
            'handle' => $account->handle,
            'displayName' => $account->display_name,
            'description' => $account->note,
            'avatar' => $account->getAvatarOrDefault(),
            'followersCount' => $account->followers_count,
            'followingCount' => $account->following_count,
        ];
    }
}
