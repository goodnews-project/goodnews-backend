<?php
declare (strict_types=1);
namespace App\Schema;

use Hyperf\Swagger\Annotation\Property;
use Hyperf\Swagger\Annotation\Schema;
use JsonSerializable;
#[Schema(title: 'StatusesFaveSchema')]
class StatusesFaveSchema implements JsonSerializable
{
    #[Property(property: 'id', title: '', type: 'int')]
    public ?int $id;
    #[Property(property: 'status_id', title: 'statuses.id, id of the status that has been faved', type: 'int')]
    public ?int $statusId;
    #[Property(property: 'account_id', title: 'account.id, id of the account that created ("did") the fave', type: 'int')]
    public ?int $accountId;
    #[Property(property: 'target_account_id', title: 'account.id, id the account owning the faved status', type: 'int')]
    public ?int $targetAccountId;
    #[Property(property: 'uri', title: 'ActivityPub URI of this fave', type: 'string')]
    public ?string $uri;
    #[Property(property: 'created_at', title: '', type: 'mixed')]
    public mixed $createdAt;
    #[Property(property: 'updated_at', title: '', type: 'mixed')]
    public mixed $updatedAt;
    public function __construct(\App\Model\StatusesFave $model)
    {
        $this->id = $model->id;
        $this->statusId = $model->status_id;
        $this->accountId = $model->account_id;
        $this->targetAccountId = $model->target_account_id;
        $this->uri = $model->uri;
        $this->createdAt = $model->created_at;
        $this->updatedAt = $model->updated_at;
    }
    public function jsonSerialize() : mixed
    {
        return ['id' => $this->id, 'status_id' => $this->statusId, 'account_id' => $this->accountId, 'target_account_id' => $this->targetAccountId, 'uri' => $this->uri, 'created_at' => $this->createdAt, 'updated_at' => $this->updatedAt];
    }
}