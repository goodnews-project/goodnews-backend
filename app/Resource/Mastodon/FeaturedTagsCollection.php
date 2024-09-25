<?php

namespace App\Resource\Mastodon;

use Hyperf\Resource\Json\ResourceCollection;

class FeaturedTagsCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return parent::toArray();
    }
}
