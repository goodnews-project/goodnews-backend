<?php

namespace App\Resource\Mastodon;

use Hyperf\Resource\Json\ResourceCollection;

class InstanceRuleCollection extends ResourceCollection
{
    public ?string $wrap = null;
    /**
     * Transform the resource collection into an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->collection->map(function ($rule, $key) {
            $id = $key + 1;
            return ['id' => (string)$id, 'text' => (string)$rule->text];
        })->toArray();
    }
}
