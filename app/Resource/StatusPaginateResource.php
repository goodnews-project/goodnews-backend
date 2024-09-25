<?php

namespace App\Resource;

use Hyperf\Paginator\LengthAwarePaginator;
use Hyperf\Resource\Json\JsonResource;

class StatusPaginateResource extends JsonResource
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
        if (!$this->resource instanceof LengthAwarePaginator) {
            return [];
        }
        $statusPaginator = $this->resource;
        $this->setUnlockAttachmentsByStatusPaginator($statusPaginator);
        return $statusPaginator->toArray();
    }
}
