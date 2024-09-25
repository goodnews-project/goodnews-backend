<?php

namespace App\Resource\Mastodon;

use Hyperf\Resource\Json\ResourceCollection;

class EmojiCollection extends ResourceCollection
{
    public ?string $wrap = null;
    /**
     * Transform the resource collection into an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->collection->transform(function ($emoji) {
            return [
                'shortcode' =>(string) $emoji['shortcode'],
                'url' => (string)$emoji['image_url'],
                'static_url' => (string)$emoji['image_url'],
                'visible_in_picker' => (bool) $emoji['visible_in_picker'],
                'category' => 'default'
            ];
        })->filter()->toArray();
    }
}
