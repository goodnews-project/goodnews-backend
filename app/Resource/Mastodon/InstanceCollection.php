<?php

namespace App\Resource\Mastodon;

use App\Service\AttachmentService;
use Hyperf\Resource\Json\ResourceCollection;

class InstanceCollection extends ResourceCollection
{
    public ?string $wrap = null;
    /**
     * Transform the resource collection into an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            $this->merge($this->collection),
            'configuration' => [
                'media_attachments' => [
                    'image_matrix_limit' => 16777216,
                    'image_size_limit' => 1500 * 1024,
                    'supported_mime_types' => array_keys(AttachmentService::$MIMES),
                    'video_frame_rate_limit' => 120,
                    'video_matrix_limit' => 2304000,
                    'video_size_limit' => 1500 * 1024,
                ],
                'polls' => [
                    'max_characters_per_option' => 50,
                    'max_expiration' => 2629746,
                    'max_options' => 4,
                    'min_expiration' => 300
                ],
                'statuses' => [
                    'characters_reserved_per_url' => 23,
                    'max_characters' => 5000,
                    'max_media_attachments' => 20
                ]
            ]
        ];
    }
}
