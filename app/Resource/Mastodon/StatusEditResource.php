<?php

namespace App\Resource\Mastodon;

use Carbon\Carbon;
use Hyperf\Resource\Json\JsonResource;
use App\Model\StatusEdit as StatusEditModel;

class StatusEditResource extends JsonResource
{
    public ?string $wrap = null;
    /**
     * Transform the resource into an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        if (!$this->resource instanceof StatusEditModel) {
            return [];
        }
        $statusEdit = $this->resource;
        return [
            'content' => (string) $statusEdit->content,
            'spoiler_text' => (string) $statusEdit->spoiler_text,
            'sensitive' => (bool) $statusEdit->is_sensitive,
            'created_at' => $statusEdit->created_at?->toIso8601ZuluString('m') ?: Carbon::now()->toIso8601ZuluString('m'),
            'poll' => $statusEdit->poll_options,
            'account' => AccountResource::make($statusEdit->account),
            'media_attachments' => AccountResource::make($statusEdit->account),
            'emojis' => EmojiCollection::make($statusEdit->emoji),
        ];
    }
}
