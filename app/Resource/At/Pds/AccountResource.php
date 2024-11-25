<?php

namespace App\Resource\At\Pds;

use Hyperf\Resource\Json\JsonResource;

class AccountResource extends JsonResource
{
    public ?string $wrap = null;
    /**
     * Transform the resource into an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        $account = $this->resource;
        return [
            'users' => [
                'did' => $account->did,
                'handle' => $account->handle,
                'displayName' => $account->display_name,
                'avatar' => $account->avatar,
            ]
        ];
    }
}
