<?php

namespace App\Resource\Mastodon;

use Hyperf\Resource\Json\ResourceCollection;

class FilterKeywordCollection extends ResourceCollection
{
    public ?string $wrap = null;
    /**
     * Transform the resource collection into an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->collection->transform(function ($kw) {
            return [
                'id' => (string) $kw['key'],
                'keyword' => (string) $kw['kw'],
                'whole_word' => (bool) $kw['whole_word'],
            ];
        })->toArray();
    }
}
