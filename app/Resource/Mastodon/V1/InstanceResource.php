<?php

namespace App\Resource\Mastodon\V1;

use App\Resource\Mastodon\AccountResource;
use App\Resource\Mastodon\InstanceRuleCollection;
use Hyperf\Resource\Json\JsonResource;
use function Hyperf\Support\env;

class InstanceResource extends JsonResource
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
            'uri' => env('AP_HOST'),
            'title' => $this->resource['settings']['site_title'] ?? '',
            'short_description' => $this->resource['settings']['site_short_description'] ?? '',
            'description' => $this->resource['settings']['site_short_description'] ?? '',
            'email' => $this->resource['settings']['site_contact_email'] ?? '',
            'version' => '0.0.1',
            'urls' => ['streaming_api' => 'wss://'],
            'stats' => [
                'user_count' => 1523122,
                'status_count' => 9123122,
                'domain_count' => 4123122,
            ],
            'thumbnail' => $this->resource['settings']['thumbnail_url'] ?? '',
            'languages' => ['en', 'zh'],
            'registrations' => true,
            'approval_required' => false,
            'invites_enabled' => true,
            'configuration' => [
                'statuses' => [
                    'max_characters' => 500,
                    'max_media_attachments' => 4,
                    'characters_reserved_per_url' => 23,
                ],
                'media_attachments' => [
                    'supported_mime_types' => [
                        "image/jpeg",
                        "image/png",
                        "image/gif",
                        "image/webp",
                        "video/webm",
                        "video/mp4",
                        "video/quicktime",
                        "video/ogg",
                        "audio/wave",
                        "audio/wav",
                        "audio/x-wav",
                        "audio/x-pn-wave",
                        "audio/vnd.wave",
                        "audio/ogg",
                        "audio/vorbis",
                        "audio/mpeg",
                        "audio/mp3",
                        "audio/webm",
                        "audio/flac",
                        "audio/aac",
                        "audio/m4a",
                        "audio/x-m4a",
                        "audio/mp4",
                        "audio/3gpp",
                        "video/x-ms-asf"
                    ],
                    'image_size_limit' => 10485760,
                    'image_matrix_limit' => 16777216,
                    'video_size_limit' => 41943040,
                    'video_frame_rate_limit' => 60,
                    'video_matrix_limit' => 2304000,
                ],
                'polls' => [
                    'max_options' => 4,
                    'max_characters_per_option' => 50,
                    'min_expiration' => 300,
                    'max_expiration' => 2629746,
                ],
            ],
            'contact_account' => AccountResource::make($this->resource['contactAccount']),
            'rules' => InstanceRuleCollection::make($this->resource['rules']),
        ];
    }
}
