<?php

namespace App\Resource\At\Pds;

use App\Model\Attachment;
use App\Model\Status;
use Hyperf\Resource\Json\JsonResource;

class StatusResource extends JsonResource
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
            'uri' => $status->uri,
            'cid' => $status->cid,
            'author' => [
                'did' => $status->account->did,
                'handle' => $status->account->handle,
                'displayName' => $status->account->display_name,
            ],
            'record' => [
                'text' => $status->content,
                'createdAt' => $status->published_at,
                'media' => $status->attachments->transform(function (Attachment $attachment) {
                    return [
                        'type' => $attachment->type,
                        'url' => $attachment->url,
                        'alt' => $attachment->name,
                    ];
                })
            ]
        ];
    }
}
