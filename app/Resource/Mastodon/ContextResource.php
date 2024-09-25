<?php

namespace App\Resource\Mastodon;

use App\Model\Status;
use Hyperf\Resource\Json\JsonResource;

use function Hyperf\Collection\collect;

class ContextResource extends JsonResource
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
            'ancestors' => collect($this->resource['ancestors'])->transform(function (Status $status) {
                return StatusResource::make($status);
            }),
            'descendants' => collect($this->resource['descendants'])->transform(function (Status $status) {
                return StatusResource::make($status);
            }),
        ];
    }
}
