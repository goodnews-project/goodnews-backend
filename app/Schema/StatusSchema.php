<?php
declare (strict_types=1);
namespace App\Schema;

use Hyperf\Swagger\Annotation\Property;
use Hyperf\Swagger\Annotation\Schema;
use JsonSerializable;
#[Schema(title: 'StatusSchema')]
class StatusSchema implements JsonSerializable
{
    #[Property(property: 'id', title: '', type: 'int')]
    public ?int $id;
    #[Property(property: 'account_id', title: 'account.id, which account posted this status', type: 'int')]
    public ?int $accountId;
    #[Property(property: 'reply_to_id', title: 'statuses.id, id of the status this status replies to', type: 'int')]
    public ?int $replyToId;
    #[Property(property: 'reply_to_account_id', title: 'account.id, id of the account that this status replies to', type: 'int')]
    public ?int $replyToAccountId;
    #[Property(property: 'reblog_id', title: 'statuses.id, id of the statuses that this status reblog to', type: 'int')]
    public ?int $reblogId;
    #[Property(property: 'uri', title: 'activitypub URI of this status', type: 'string')]
    public ?string $uri;
    #[Property(property: 'url', title: 'web url for viewing this status', type: 'string')]
    public ?string $url;
    #[Property(property: 'content', title: 'content of this status; likely html-formatted but not guaranteed', type: 'mixed')]
    public mixed $content;
    #[Property(property: 'is_local', title: 'is this status from a local account', type: 'int')]
    public ?int $isLocal;
    #[Property(property: 'is_sensitive', title: 'mark the status as sensitive', type: 'int')]
    public ?int $isSensitive;
    #[Property(property: 'pinned_at', title: 'Status was pinned by owning account at this time.', type: 'mixed')]
    public mixed $pinnedAt;
    #[Property(property: 'fave_count', title: 'fave count of this status', type: 'int')]
    public ?int $faveCount;
    #[Property(property: 'reply_count', title: 'reply count of this status', type: 'int')]
    public ?int $replyCount;
    #[Property(property: 'reblog_count', title: 'reblog count of this status', type: 'int')]
    public ?int $reblogCount;
    #[Property(property: 'created_at', title: '', type: 'mixed')]
    public mixed $createdAt;
    #[Property(property: 'updated_at', title: '', type: 'mixed')]
    public mixed $updatedAt;
    #[Property(property: 'scope', title: '', type: 'int')]
    public ?int $scope;
    public function __construct(\App\Model\Status $model)
    {
        $this->id = $model->id;
        $this->accountId = $model->account_id;
        $this->replyToId = $model->reply_to_id;
        $this->replyToAccountId = $model->reply_to_account_id;
        $this->reblogId = $model->reblog_id;
        $this->uri = $model->uri;
        $this->url = $model->url;
        $this->content = $model->content;
        $this->isLocal = $model->is_local;
        $this->isSensitive = $model->is_sensitive;
        $this->pinnedAt = $model->pinned_at;
        $this->faveCount = $model->fave_count;
        $this->replyCount = $model->reply_count;
        $this->reblogCount = $model->reblog_count;
        $this->createdAt = $model->created_at;
        $this->updatedAt = $model->updated_at;
        $this->scope = $model->scope;
    }
    public function jsonSerialize() : mixed
    {
        return ['id' => $this->id, 'account_id' => $this->accountId, 'reply_to_id' => $this->replyToId, 'reply_to_account_id' => $this->replyToAccountId, 'reblog_id' => $this->reblogId, 'uri' => $this->uri, 'url' => $this->url, 'content' => $this->content, 'is_local' => $this->isLocal, 'is_sensitive' => $this->isSensitive, 'is_pinned' => $this->isPinned, 'pinned_at' => $this->pinnedAt, 'fave_count' => $this->faveCount, 'reply_count' => $this->replyCount, 'reblog_count' => $this->reblogCount, 'created_at' => $this->createdAt, 'updated_at' => $this->updatedAt, 'scope' => $this->scope];
    }
}