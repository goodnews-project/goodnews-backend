<?php
declare (strict_types=1);
namespace App\Schema;

use Hyperf\Swagger\Annotation\Property;
use Hyperf\Swagger\Annotation\Schema;
use JsonSerializable;
#[Schema(title: 'AccountSchema')]
class AccountSchema implements JsonSerializable
{
    #[Property(property: 'id', title: '', type: 'int')]
    public ?int $id;
    #[Property(property: 'username', title: 'The username of the account, not including domain', type: 'string')]
    public ?string $username;
    #[Property(property: 'acct', title: 'Equal to username for local users, or username@domain for remote users', type: 'string')]
    public ?string $acct;
    #[Property(property: 'domain', title: 'Domain of the account', type: 'string')]
    public ?string $domain;
    #[Property(property: 'display_name', title: 'The display name of account', type: 'string')]
    public ?string $displayName;
    #[Property(property: 'suspended_at', title: 'When was this account suspended (eg., don/t allow it to log in/post, don/t accept media/posts from this account)', type: 'mixed')]
    public mixed $suspendedAt;
    #[Property(property: 'is_sensitive', title: 'Set posts from this account to sensitive by default', type: 'int')]
    public ?int $isSensitive;
    #[Property(property: 'note', title: 'Bio/description of this account', type: 'string')]
    public ?string $note;
    #[Property(property: 'avatar', title: 'The avatar url of account', type: 'string')]
    public ?string $avatar;
    #[Property(property: 'avatar_remote_url', title: 'For a non-local account, where can the header be fetched?', type: 'string')]
    public ?string $avatarRemoteUrl;
    #[Property(property: 'uri', title: 'ActivityPub URI for this account.', type: 'string')]
    public ?string $uri;
    #[Property(property: 'url', title: 'Web location of the account/s profile page', type: 'string')]
    public ?string $url;
    #[Property(property: 'inbox_uri', title: 'Address of this account/s ActivityPub inbox, for sending activity to', type: 'string')]
    public ?string $inboxUri;
    #[Property(property: 'shared_inbox_uri', title: 'Address of this account/s ActivityPub sharedInbox. Gotcha warning: this is a string pointer because it has three possible states: 1. We don/t know yet if the account has a shared inbox -- null. 2. We know it doesn/t have a shared inbox -- empty string. 3. We know it does have a shared inbox -- url string', type: 'string')]
    public ?string $sharedInboxUri;
    #[Property(property: 'outbox_uri', title: 'Address of this account/s activitypub outbox', type: 'string')]
    public ?string $outboxUri;
    #[Property(property: 'following_uri', title: 'URI for getting the following list of this account', type: 'string')]
    public ?string $followingUri;
    #[Property(property: 'followers_uri', title: 'URI for getting the followers list of this account', type: 'string')]
    public ?string $followersUri;
    #[Property(property: 'public_key_uri', title: 'Web-reachable location of this account/s public key', type: 'string')]
    public ?string $publicKeyUri;
    #[Property(property: 'public_key', title: 'Publickey for encoding activitypub requests, will be defined for both local and remote accounts', type: 'mixed')]
    public mixed $publicKey;
    #[Property(property: 'private_key', title: 'Privatekey for validating activitypub requests, will only be defined for local accounts', type: 'mixed')]
    public mixed $privateKey;
    #[Property(property: 'language', title: 'What language does this account post in', type: 'string')]
    public ?string $language;
    #[Property(property: 'followers_count', title: 'Number of accounts following this account', type: 'int')]
    public ?int $followersCount;
    #[Property(property: 'following_count', title: 'Number of accounts followed by this account', type: 'int')]
    public ?int $followingCount;
    #[Property(property: 'actor_type', title: 'One of [1:Application 2:Group 3:Organization 4:Person 5:Service]', type: 'int')]
    public ?int $actorType;
    #[Property(property: 'is_activate', title: '是否激活', type: 'int')]
    public ?int $isActivate;
    #[Property(property: 'last_webfingered_at', title: 'Last time this account was refreshed/located with webfinger', type: 'mixed')]
    public mixed $lastWebfingeredAt;
    #[Property(property: 'created_at', title: '', type: 'mixed')]
    public mixed $createdAt;
    #[Property(property: 'updated_at', title: '', type: 'mixed')]
    public mixed $updatedAt;
    public function __construct(\App\Model\Account $model)
    {
        $this->id = $model->id;
        $this->username = $model->username;
        $this->acct = $model->acct;
        $this->domain = $model->domain;
        $this->displayName = $model->display_name;
        $this->suspendedAt = $model->suspended_at;
        $this->isSensitive = $model->is_sensitive;
        $this->note = $model->note;
        $this->avatar = $model->avatar;
        $this->avatarRemoteUrl = $model->avatar_remote_url;
        $this->uri = $model->uri;
        $this->url = $model->url;
        $this->inboxUri = $model->inbox_uri;
        $this->sharedInboxUri = $model->shared_inbox_uri;
        $this->outboxUri = $model->outbox_uri;
        $this->followingUri = $model->following_uri;
        $this->followersUri = $model->followers_uri;
        $this->publicKeyUri = $model->public_key_uri;
        $this->publicKey = $model->public_key;
        $this->privateKey = $model->private_key;
        $this->language = $model->language;
        $this->followersCount = $model->followers_count;
        $this->followingCount = $model->following_count;
        $this->actorType = $model->actor_type;
        $this->isActivate = $model->is_activate;
        $this->lastWebfingeredAt = $model->last_webfingered_at;
        $this->createdAt = $model->created_at;
        $this->updatedAt = $model->updated_at;
    }
    public function jsonSerialize() : mixed
    {
        return ['id' => $this->id, 'username' => $this->username, 'acct' => $this->acct, 'domain' => $this->domain, 'display_name' => $this->displayName, 'suspended_at' => $this->suspendedAt, 'is_sensitive' => $this->isSensitive, 'note' => $this->note, 'avatar' => $this->avatar, 'avatar_remote_url' => $this->avatarRemoteUrl, 'uri' => $this->uri, 'url' => $this->url, 'inbox_uri' => $this->inboxUri, 'shared_inbox_uri' => $this->sharedInboxUri, 'outbox_uri' => $this->outboxUri, 'following_uri' => $this->followingUri, 'followers_uri' => $this->followersUri, 'public_key_uri' => $this->publicKeyUri, 'public_key' => $this->publicKey, 'private_key' => $this->privateKey, 'language' => $this->language, 'followers_count' => $this->followersCount, 'following_count' => $this->followingCount, 'actor_type' => $this->actorType, 'is_activate' => $this->isActivate, 'last_webfingered_at' => $this->lastWebfingeredAt, 'created_at' => $this->createdAt, 'updated_at' => $this->updatedAt];
    }
}