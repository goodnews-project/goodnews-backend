<?php
declare (strict_types=1);
namespace App\Schema;

use Hyperf\Swagger\Annotation\Property;
use Hyperf\Swagger\Annotation\Schema;
use JsonSerializable;
#[Schema(title: 'ConversationSchema')]
class ConversationSchema implements JsonSerializable
{
    #[Property(property: 'id', title: '', type: 'int')]
    public ?int $id;
    #[Property(property: 'c_id', title: 'conversation unique id', type: 'string')]
    public ?string $cId;
    #[Property(property: 'to_id', title: '', type: 'int')]
    public ?int $toId;
    #[Property(property: 'from_id', title: '', type: 'int')]
    public ?int $fromId;
    #[Property(property: 'dm_id', title: '', type: 'int')]
    public ?int $dmId;
    #[Property(property: 'status_id', title: '', type: 'int')]
    public ?int $statusId;
    #[Property(property: 'dm_type', title: '', type: 'int')]
    public ?int $dmType;
    #[Property(property: 'created_at', title: '', type: 'mixed')]
    public mixed $createdAt;
    #[Property(property: 'updated_at', title: '', type: 'mixed')]
    public mixed $updatedAt;
    public function __construct(\App\Model\Conversation $model)
    {
        $this->id = $model->id;
        $this->cId = $model->c_id;
        $this->toId = $model->to_id;
        $this->fromId = $model->from_id;
        $this->dmId = $model->dm_id;
        $this->statusId = $model->status_id;
        $this->dmType = $model->dm_type;
        $this->createdAt = $model->created_at;
        $this->updatedAt = $model->updated_at;
    }
    public function jsonSerialize() : mixed
    {
        return ['id' => $this->id, 'c_id' => $this->cId, 'to_id' => $this->toId, 'from_id' => $this->fromId, 'dm_id' => $this->dmId, 'status_id' => $this->statusId, 'dm_type' => $this->dmType, 'created_at' => $this->createdAt, 'updated_at' => $this->updatedAt];
    }
}