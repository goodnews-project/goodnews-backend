<?php
declare (strict_types=1);
namespace App\Schema;

use Hyperf\Swagger\Annotation\Property;
use Hyperf\Swagger\Annotation\Schema;
use JsonSerializable;
#[Schema(title: 'StatusesMentionSchema')]
class StatusesMentionSchema implements JsonSerializable
{
    #[Property(property: 'id', title: '', type: 'int')]
    public ?int $id;
    #[Property(property: 'status_id', title: 'statuses.id, ID of the status this mention originates from', type: 'int')]
    public ?int $statusId;
    #[Property(property: 'account_id', title: 'account.id, ID of the mention creator account', type: 'int')]
    public ?int $accountId;
    #[Property(property: 'target_account_id', title: 'account.id, Mention target/receiver account ID', type: 'int')]
    public ?int $targetAccountId;
    #[Property(property: 'href', title: 'mention href', type: 'string')]
    public ?string $href;
    #[Property(property: 'name', title: 'mention name', type: 'string')]
    public ?string $name;
    #[Property(property: 'silent', title: 'Prevent this mention from generating a notification', type: 'int')]
    public ?int $silent;
    #[Property(property: 'created_at', title: '', type: 'mixed')]
    public mixed $createdAt;
    #[Property(property: 'updated_at', title: '', type: 'mixed')]
    public mixed $updatedAt;
    public function __construct(\App\Model\StatusesMention $model)
    {
        $this->id = $model->id;
        $this->statusId = $model->status_id;
        $this->accountId = $model->account_id;
        $this->targetAccountId = $model->target_account_id;
        $this->href = $model->href;
        $this->name = $model->name;
        $this->silent = $model->silent;
        $this->createdAt = $model->created_at;
        $this->updatedAt = $model->updated_at;
    }
    public function jsonSerialize() : mixed
    {
        return ['id' => $this->id, 'status_id' => $this->statusId, 'account_id' => $this->accountId, 'target_account_id' => $this->targetAccountId, 'href' => $this->href, 'name' => $this->name, 'silent' => $this->silent, 'created_at' => $this->createdAt, 'updated_at' => $this->updatedAt];
    }
}