<?php

namespace App\Resource\Mastodon;

use Hyperf\Resource\Json\ResourceCollection;
use App\Model\Account;

class MentionCollection extends ResourceCollection
{
    public ?string $wrap = null;
    /**
     * Transform the resource collection into an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->collection->transform(function (Account $mention) {
            return [
                'id' => (string)$mention->id,
                'username' => (string)$mention->username,
                'url' => (string)$mention->permaurl(),
                'acct' => (string)$mention->acct,
            ];
        })->toArray();
    }
}
