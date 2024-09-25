<?php

namespace App\Resource\Mastodon;

use App\Model\Status;
use Hyperf\Resource\Json\JsonResource;

class StatusSource extends JsonResource
{
    public ?string $wrap = null;
    /**
     * Transform the resource into an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        if (!$this->resource instanceof Status) {
            return [];
        }
        $status = $this->resource;
        return [
            'id' => (string) $status->id,
            'text' => (string) $status->content,
            'spoiler_text' => '',
        ];
    }
}
