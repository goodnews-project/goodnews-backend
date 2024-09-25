<?php
declare (strict_types=1);
namespace App\Schema;

use Hyperf\Swagger\Annotation\Property;
use Hyperf\Swagger\Annotation\Schema;
use JsonSerializable;
#[Schema(title: 'DirectMessageSchema')]
class DirectMessageSchema implements JsonSerializable
{
    #[Property(property: 'id', title: '', type: 'int')]
    public ?int $id;
    #[Property(property: 'to_id', title: 'account.id, recipient', type: 'int')]
    public ?int $toId;
    #[Property(property: 'from_id', title: 'account.id, sender', type: 'int')]
    public ?int $fromId;
    #[Property(property: 'status_id', title: 'statuses.id', type: 'int')]
    public ?int $statusId;
    #[Property(property: 'dm_type', title: 'message type, 1.text 2.photo 3.video', type: 'int')]
    public ?int $dmType;
    #[Property(property: 'read_at', title: '查看时间', type: 'mixed')]
    public mixed $readAt;
    #[Property(property: 'created_at', title: '', type: 'mixed')]
    public mixed $createdAt;
    #[Property(property: 'updated_at', title: '', type: 'mixed')]
    public mixed $updatedAt;
    public function __construct(\App\Model\DirectMessage $model)
    {
        $this->id = $model->id;
        $this->toId = $model->to_id;
        $this->fromId = $model->from_id;
        $this->statusId = $model->status_id;
        $this->dmType = $model->dm_type;
        $this->readAt = $model->read_at;
        $this->createdAt = $model->created_at;
        $this->updatedAt = $model->updated_at;
    }
    public function jsonSerialize() : mixed
    {
        return ['id' => $this->id, 'to_id' => $this->toId, 'from_id' => $this->fromId, 'status_id' => $this->statusId, 'dm_type' => $this->dmType, 'read_at' => $this->readAt, 'created_at' => $this->createdAt, 'updated_at' => $this->updatedAt];
    }
}