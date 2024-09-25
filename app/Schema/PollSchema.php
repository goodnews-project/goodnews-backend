<?php
declare (strict_types=1);
namespace App\Schema;

use Hyperf\Swagger\Annotation\Property;
use Hyperf\Swagger\Annotation\Schema;
use JsonSerializable;
#[Schema(title: 'PollSchema')]
class PollSchema implements JsonSerializable
{
    #[Property(property: 'id', title: '', type: 'int')]
    public ?int $id;
    #[Property(property: 'status_id', title: 'statuses.id', type: 'int')]
    public ?int $statusId;
    #[Property(property: 'account_id', title: 'account.id', type: 'int')]
    public ?int $accountId;
    #[Property(property: 'poll_options', title: '投票的选项，是一个字符串数组。', type: 'mixed')]
    public mixed $pollOptions;
    #[Property(property: 'cached_tallies', title: '缓存的统计信息，包含各个选项的投票计数的数组。', type: 'mixed')]
    public mixed $cachedTallies;
    #[Property(property: 'multiple', title: '用户是否可以选择多个选项进行投票', type: 'int')]
    public ?int $multiple;
    #[Property(property: 'hide_totals', title: '是否隐藏投票总数', type: 'int')]
    public ?int $hideTotals;
    #[Property(property: 'votes_count', title: '投票总数', type: 'int')]
    public ?int $votesCount;
    #[Property(property: 'voters_count', title: '参与投票的用户数', type: 'int')]
    public ?int $votersCount;
    #[Property(property: 'last_fetched_at', title: '最后一次获取投票信息的时间。', type: 'mixed')]
    public mixed $lastFetchedAt;
    #[Property(property: 'expires_at', title: '投票的过期时间', type: 'mixed')]
    public mixed $expiresAt;
    #[Property(property: 'created_at', title: '', type: 'mixed')]
    public mixed $createdAt;
    #[Property(property: 'updated_at', title: '', type: 'mixed')]
    public mixed $updatedAt;
    public function __construct(\App\Model\Poll $model)
    {
        $this->id = $model->id;
        $this->statusId = $model->status_id;
        $this->accountId = $model->account_id;
        $this->pollOptions = $model->poll_options;
        $this->cachedTallies = $model->cached_tallies;
        $this->multiple = $model->multiple;
        $this->hideTotals = $model->hide_totals;
        $this->votesCount = $model->votes_count;
        $this->votersCount = $model->voters_count;
        $this->lastFetchedAt = $model->last_fetched_at;
        $this->expiresAt = $model->expires_at;
        $this->createdAt = $model->created_at;
        $this->updatedAt = $model->updated_at;
    }
    public function jsonSerialize() : mixed
    {
        return ['id' => $this->id, 'status_id' => $this->statusId, 'account_id' => $this->accountId, 'poll_options' => $this->pollOptions, 'cached_tallies' => $this->cachedTallies, 'multiple' => $this->multiple, 'hide_totals' => $this->hideTotals, 'votes_count' => $this->votesCount, 'voters_count' => $this->votersCount, 'last_fetched_at' => $this->lastFetchedAt, 'expires_at' => $this->expiresAt, 'created_at' => $this->createdAt, 'updated_at' => $this->updatedAt];
    }
}