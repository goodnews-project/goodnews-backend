<?php

namespace App\Resource;

use App\Model\Status;
use Hyperf\Resource\Json\JsonResource;

class StatusResource extends JsonResource
{
    use Process;
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
        $this->setUnlockAttachmentsByStatus($status);
        return $status->toArray();
    }
}
