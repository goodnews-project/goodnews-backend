<?php

namespace App\Resource\Mastodon;

use Hyperf\Resource\Json\JsonResource;

class SearchResource extends JsonResource
{
    public ?string $wrap = null;
    /**
     * Transform the resource into an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'accounts' => AccountCollection::make($this->resource['accounts']),
            'statuses' => StatusCollection::make($this->resource['statuses']),
            'hashtags' => TagCollection::make($this->resource['hashtags']),
        ];
    }
}
