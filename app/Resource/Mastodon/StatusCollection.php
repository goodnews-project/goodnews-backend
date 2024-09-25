<?php

namespace App\Resource\Mastodon;

use App\Model\Status;
use Hyperf\Resource\Json\ResourceCollection;

class StatusCollection extends ResourceCollection
{
    public ?string $wrap = null;
    /**
     * Transform the resource collection into an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->collection->transform(function (Status $status) {
            return StatusResource::make($status);
        })->toArray();

    }



}
