<?php

namespace App\Resource\Mastodon;

use Hyperf\Resource\Json\JsonResource;

class ApplicationResource extends JsonResource
{
    public ?string $wrap = null;
    /**
     * Transform the resource into an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        $client = $this->resource;
        return [
            'id' => (string) $client->id,
            'name' => $client->name,
            'website' => null,
            'redirect_uri' => $client->redirect,
            'client_id' => (string) $client->id,
            'client_secret' => $client->secret,
            'vapid_key' => null
        ];
    }
}
