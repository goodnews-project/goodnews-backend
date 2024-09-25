<?php

namespace App\Resource\Mastodon;

use App\Model\Filter;
use Hyperf\Resource\Json\JsonResource;

class FilterResource extends JsonResource
{
    public ?string $wrap = null;
    /**
     * Transform the resource into an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        if (!$this->resource instanceof Filter) {
            return [];
        }

        $filter = $this->resource;
        return [
            'id' => (string) $filter->id,
            'title' => (string) $filter->title,
            'context' => $filter->context_enum,
            'expires_at' => $filter->expired_at?->toIso8601String(),
            'filter_action' => Filter::actMap[$filter->act],
            'keywords' => FilterKeywordCollection::make($filter->kw_attr ?? []),
        ];
    }
}
