<?php

namespace App\Resource\Mastodon;

use Hyperf\Resource\Json\JsonResource;

class TokenResource extends JsonResource
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
            'created_at' => (int) $this->resource['created_at'],
            'scope' => (string) $this->resource['scope'],
            'token_type' => (string) $this->resource['token_type'],
            'expires_in' => (int) $this->resource['expires_in'],
            'access_token' => (string) $this->resource['access_token'],
        ];
    }
}
