<?php

namespace App\Resource\Mastodon;

use App\Model\Hashtag;
use Hyperf\Resource\Json\ResourceCollection;

class TagCollection extends ResourceCollection
{
    public ?string $wrap = null;
    /**
     * Transform the resource collection into an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->collection->transform(function (Hashtag $hashtag) {
            return TagResource::make($hashtag);
        })->toArray();
    }
}
