<?php

namespace App\Resource;

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
            "domain"      => env('AP_HOST'), 
            "title"       => $this->resource['settings']['site_title'] ?? '', 
            "version"     => "0.0.1", 
            "source_url"  => "https://github.com/", 
            "description" => $this->resource['settings']['site_short_description'] ?? '', 
            "usage"       => [
                "users" => [
                    "active_month" => $this->resource['activeUserCount'] ?? 0 
                ] 
            ], 
            "thumbnail" => [
                "url"      => $this->resource['settings']['thumbnail_url'] ?? '', 
                "blurhash" => "", 
                "versions" => [
                    "@1x" => $this->resource['settings']['thumbnail_url'] ?? '', 
                    "@2x" => $this->resource['settings']['thumbnail_url'] ?? '' 
                ] 
            ], 
            "languages"     => ["en"] , 
            "configuration" => [
                "urls" => [
                    "streaming" => "wss://" 
                ], 
                "vapid" => [
                    "public_key" => "" 
                ], 
                "accounts" => [
                    "max_featured_tags" => 10 
                ], 
                "statuses" => [
                    "max_characters"              => 500, 
                    "max_media_attachments"       => 4, 
                    "characters_reserved_per_url" => 23 
                ], 
                "media_attachments" => [
                    "supported_mime_types" => [
                        "image/jpeg", 
                        "image/png", 
                        "image/gif", 
                        "image/heic", 
                        "image/heif", 
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
                    ]
                ], 
                "image_size_limit"       => 10485760, 
                "image_matrix_limit"     => 16777216, 
                "video_size_limit"       => 41943040, 
                "video_frame_rate_limit" => 60, 
                "video_matrix_limit"     => 2304000,
                "polls"                  => [
                    "max_options"               => 4, 
                    "max_characters_per_option" => 50, 
                    "min_expiration"            => 300, 
                    "max_expiration"            => 2629746 
                ], 
                "translation" => [
                        "enabled" => true 
                ] 
            ], 
            "registrations" => [
                "enabled"           => false, 
                "approval_required" => false, 
                "message"           => null 
            ], 
            "contact" => [
                "email"   => $this->resource['settings']['site_contact_email'] ?? '', 
                "account" => [
                    "id"              => $this->resource['contactAccount']['id'], 
                    "username"        => $this->resource['contactAccount']['username'], 
                    "acct"            => $this->resource['contactAccount']['acct'], 
                    "display_name"    => $this->resource['contactAccount']['display_name'], 
                    "locked"          => false, 
                    "bot"             => false, 
                    "discoverable"    => true, 
                    "group"           => false, 
                    "created_at"      => $this->resource['contactAccount']['created_at'], 
                    "note"            => (string)$this->resource['contactAccount']['note'], 
                    "url"             => $this->resource['contactAccount']['url'], 
                    "avatar"          => $this->resource['contactAccount']['avatar'], 
                    "avatar_static"   => $this->resource['contactAccount']['avatar'], 
                    "header"          => $this->resource['contactAccount']['profile_image'], 
                    "header_static"   => $this->resource['contactAccount']['profile_image'], 
                    "followers_count" => $this->resource['contactAccount']['followers_count'], 
                    "following_count" => $this->resource['contactAccount']['following_count'], 
                    "statuses_count"  => 72605, 
                    "last_status_at"  => "2022-10-31", 
                    "noindex"         => false, 
                    "emojis"          => [
                    ]
                ]
            ],
            "rules" => $this->resource['rules']
        ];
    }

}
