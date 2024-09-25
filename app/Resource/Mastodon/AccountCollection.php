<?php

namespace App\Resource\Mastodon;

use App\Model\Account;
use Hyperf\Resource\Json\ResourceCollection;

class AccountCollection extends ResourceCollection
{
    public ?string $wrap = null;
    /**
     * Transform the resource collection into an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->collection->transform(function (Account $account) {
            return AccountResource::make($account);
        })->toArray();
    }
}
