<?php

namespace App\Resource\Mastodon;

use App\Model\Filter;
use App\Model\UserFilter;
use Hyperf\Resource\Json\ResourceCollection;

class FilterResultCollection extends ResourceCollection
{
    public ?string $wrap = null;
    /**
     * Transform the resource collection into an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->collection->transform(function (Filter $filter) {
            return [
                'filter' => FilterResource::make($filter),
                'keyword_matches' => [],
                'status_matches' => [],
            ];
        })->toArray();
    }
}
