<?php
declare (strict_types=1);
namespace App\Schema;

use Hyperf\Swagger\Annotation\Property;
use Hyperf\Swagger\Annotation\Schema;
use JsonSerializable;
#[Schema(title: 'StatusHashtagSchema')]
class StatusHashtagSchema implements JsonSerializable
{
    #[Property(property: 'id', title: '', type: 'int')]
    public ?int $id;
    #[Property(property: 'status_id', title: 'statuses.id', type: 'int')]
    public ?int $statusId;
    #[Property(property: 'hashtag_id', title: 'hashtags.id', type: 'int')]
    public ?int $hashtagId;
    #[Property(property: 'account_id', title: 'account.id', type: 'int')]
    public ?int $accountId;
    #[Property(property: 'created_at', title: '', type: 'mixed')]
    public mixed $createdAt;
    #[Property(property: 'updated_at', title: '', type: 'mixed')]
    public mixed $updatedAt;
    public function __construct(\App\Model\StatusHashtag $model)
    {
        $this->id = $model->id;
        $this->statusId = $model->status_id;
        $this->hashtagId = $model->hashtag_id;
        $this->accountId = $model->account_id;
        $this->createdAt = $model->created_at;
        $this->updatedAt = $model->updated_at;
    }
    public function jsonSerialize() : mixed
    {
        return ['id' => $this->id, 'status_id' => $this->statusId, 'hashtag_id' => $this->hashtagId, 'account_id' => $this->accountId, 'created_at' => $this->createdAt, 'updated_at' => $this->updatedAt];
    }
}