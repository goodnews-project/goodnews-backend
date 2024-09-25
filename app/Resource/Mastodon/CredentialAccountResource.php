<?php

namespace App\Resource\Mastodon;

use App\Model\Account;
use Hyperf\Resource\Json\JsonResource;

class CredentialAccountResource extends JsonResource
{
    public ?string $wrap = null;
    /**
     * Transform the resource into an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        if (!$this->resource instanceof Account) {
            return [];
        }

        $account = $this->resource;
        return [
            'id' => (string) $account->id,
            'username' => $account->username,
            'acct' => $account->acct,
            'display_name' => $account->display_name,
            'locked' => false,
            'bot' => false,
            'group' => false,
            'created_at' => $account->created_at->toAtomString(),
            'note' => (string) $account->note,
            'url' => $account->permaurl(),
            'avatar' => $account->getAvatarOrDefault(),
            'avatar_static' => $account->getAvatarOrDefault(),
            'header' => $account->getAvatarOrDefault(),
            'header_static' => $account->getAvatarOrDefault(),
            'followers_count' => $account->followers_count,
            'following_count' => $account->following_count,
            'statuses_count' => $account->status_count,
            'last_status_at' => null,
            'emojis' => EmojiCollection::make($account->emoji),
            'source' => [
                'privacy' => 'public',
                'sensitive' => false,
                'language' => $account->language ?: 'en',
                'note' => $account->note ? strip_tags($account->note) : '',
                'fields' => [],
                'follow_requests_count' => 1
            ],
            'fields' => \Hyperf\Collection\collect($account->fields ?? [])->transform(function ($item) {
                return [
                    'name' => $item['name'] ?? '',
                    'value' => $item['value'] ?? '',
                    'verified_at' => null,
                ];
            }),
            'role' => [
                'id' => -99,
                'name' => '',
                'permissions' => 65536,
                'color' => '',
                'highlighted' => false,
            ]
        ];
    }
}
