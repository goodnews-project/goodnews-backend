<?php

namespace App\Resource\Mastodon;

use App\Model\Hashtag;
use Hyperf\Resource\Json\JsonResource;

use function Hyperf\Support\env;

class TagResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        $hashtag = $this->resource;

        if (empty($hashtag->href)) {
            $hashtag->href = getApHostUrl().'/explore/hashtag/'.urlencode($hashtag->name);
        }

        return [
            'name' => (string)$hashtag->name,
            'url' => (string)$hashtag->href,
            'history'=> [

            ],
            'following'=> (boolean)false
        ];
    }
}
