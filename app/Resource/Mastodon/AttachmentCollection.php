<?php

namespace App\Resource\Mastodon;

use App\Model\Attachment;
use Hyperf\Resource\Json\ResourceCollection;

class AttachmentCollection extends ResourceCollection
{
    public ?string $wrap = null;
    /**
     * Transform the resource collection into an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->collection->transform(function (Attachment $attachment) {
            return AttachmentResource::make($attachment);
        })->toArray();
    }
}
