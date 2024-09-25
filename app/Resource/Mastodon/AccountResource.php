<?php

namespace App\Resource\Mastodon;

use App\Model\Account;
use App\Resource\Mastodon\Model\AccountModel;
use App\Resource\Mastodon\Model\AccountRoleModel;
use App\Resource\Mastodon\Model\SourceModel;
use Carbon\Carbon;
use Hyperf\Resource\Json\JsonResource;

class AccountResource extends JsonResource
{
    public ?string $wrap = null;
    /**
     * Transform the resource collection into an array.
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
            'username' => (string)$account->username,
            'acct' => (string)$account->acct,
            'display_name' => (string)$account->display_name,
            'locked' => false,
            'bot' => $account->isBot(),
            'discoverable' => true,
            'group' => false,
            'created_at' => $account->created_at?->toIso8601ZuluString('m') ?: Carbon::now()->toIso8601ZuluString('m'),
            'note' => (string) $account->note_rendered,
            'url' => $account->permaurl(),
            'avatar' => (string)$account->getAvatarOrDefault(),
            'avatar_static' => (string)$account->getAvatarOrDefault(),
            'header' => (string)$account->getAvatarOrDefault(),
            'header_static' => (string)$account->getAvatarOrDefault(),
            'followers_count' => (int)$account->followers_count,
            'following_count' => (int)$account->following_count,
            'statuses_count' => (int)$account->status_count,
            'last_status_at' => null,
            'emojis' => EmojiCollection::make($account->emoji),
            'fields' => \Hyperf\Collection\collect($account->fields ?? [])->transform(function ($item) {
                return [
                    'name' => $item['name'] ?? '',
                    'value' => $item['value'] ?? '',
                    'verified_at' => null,
                ];
            })
        ];
    }

    public function getModel()
    {
        if (!$this->resource instanceof Account) {
            return [];
        }

        $account = $this->resource;
        $accountModel = new AccountModel();
        $accountModel->id = (string) $account->id;
        $accountModel->username = (string) $account->username;
        $accountModel->acct = (string) $account->acct;
        $accountModel->display_name = (string) $account->display_name;
        $accountModel->locked = false;
        $accountModel->bot = false;
        $accountModel->discoverable = true;
        $accountModel->created_at = $account->created_at?->toIso8601String() ?: Carbon::now()->toIso8601String();
        $accountModel->note = (string) $account->note;
        $accountModel->url = (string) $account->permaurl();
        $accountModel->avatar = (string) $account->getAvatarOrDefault();
        $accountModel->avatar_static = (string) $account->getAvatarOrDefault();
        $accountModel->header = (string) $account->profile_image;
        $accountModel->header_static = (string) $account->profile_image;
        $accountModel->followers_count = $account->followers_count;
        $accountModel->following_count = $account->following_count;
        $accountModel->statuses_count = $account->status_count ?? 0;
        $accountModel->last_status_at = '';
        $accountModel->emojis = EmojiCollection::make($account->emoji)->toArray();
        $accountModel->fields = [];
        $accountModel->custom_css = '';
        $accountModel->enable_rss = false;
        $accountModel->moved = null;
        $accountModel->mute_expires_at = '';
        $role = new AccountRoleModel();
        $role->name = '';
        $accountModel->role = $role;
        $sourceModel = new SourceModel();
        $sourceModel->note = '';
        $sourceModel->fields = [];
        $sourceModel->also_known_as_uris = [];
        $sourceModel->follow_requests_count = 1000;
        $sourceModel->language = 'en';
        $sourceModel->privacy = '';
        $sourceModel->sensitive = false;
        $sourceModel->status_content_type = '';
        $accountModel->source = $sourceModel;
        $accountModel->suspended = is_null($account->suspended_at);
        return $accountModel;
    }
}
