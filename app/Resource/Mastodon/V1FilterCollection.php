<?php

namespace App\Resource\Mastodon;

use App\Model\UserFilter;
use Hyperf\Resource\Json\ResourceCollection;

class V1FilterCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->collection->transform(function (UserFilter $userFilter) {
            return [
                'id' => (string) $userFilter->id,
                'phrase' => (string) $userFilter->status->content,
                'context' => $userFilter->filter->context_enum,
                'whole_word' => true,
                'expires_at' => $userFilter->filter?->expired_at?->toIso8601String(),
                'irreversible' => false
            ];
        })->toArray();
    }
}
