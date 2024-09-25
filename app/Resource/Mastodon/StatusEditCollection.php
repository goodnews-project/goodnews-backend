<?php

namespace App\Resource\Mastodon;

use App\Model\StatusEdit;
use Hyperf\Resource\Json\ResourceCollection;

class StatusEditCollection extends ResourceCollection
{
    public ?string $wrap = null;
    /**
     * Transform the resource collection into an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->collection->transform(function (StatusEdit $statusEdit) {
            return StatusEditResource::make($statusEdit);
        })->toArray();
    }
}
