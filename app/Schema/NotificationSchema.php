<?php
declare (strict_types=1);
namespace App\Schema;

use Hyperf\Swagger\Annotation\Property;
use Hyperf\Swagger\Annotation\Schema;
use JsonSerializable;
#[Schema(title: 'NotificationSchema')]
class NotificationSchema implements JsonSerializable
{
    #[Property(property: 'id', title: '', type: 'int')]
    public ?int $id;
    #[Property(property: 'status_id', title: 'statuses.id, If the notification pertains to a status, what is the database ID of that status', type: 'int')]
    public ?int $statusId;
    #[Property(property: 'account_id', title: 'account.id, ID of the account that performed the action that created the notification', type: 'int')]
    public ?int $accountId;
    #[Property(property: 'target_account_id', title: 'account.id, ID of the account targeted by the notification (ie., who will receive the notification?)', type: 'int')]
    public ?int $targetAccountId;
    #[Property(property: 'notify_type', title: 'Type of this notification; 1:follow 2:mention 3:reblog 4:favourite 5:status', type: 'int')]
    public ?int $notifyType;
    #[Property(property: 'read', title: 'Notification has been seen/read', type: 'int')]
    public ?int $read;
    #[Property(property: 'created_at', title: '', type: 'mixed')]
    public mixed $createdAt;
    #[Property(property: 'updated_at', title: '', type: 'mixed')]
    public mixed $updatedAt;
    public function __construct(\App\Model\Notification $model)
    {
        $this->id = $model->id;
        $this->statusId = $model->status_id;
        $this->accountId = $model->account_id;
        $this->targetAccountId = $model->target_account_id;
        $this->notifyType = $model->notify_type;
        $this->read = $model->read;
        $this->createdAt = $model->created_at;
        $this->updatedAt = $model->updated_at;
    }
    public function jsonSerialize() : mixed
    {
        return ['id' => $this->id, 'status_id' => $this->statusId, 'account_id' => $this->accountId, 'target_account_id' => $this->targetAccountId, 'notify_type' => $this->notifyType, 'read' => $this->read, 'created_at' => $this->createdAt, 'updated_at' => $this->updatedAt];
    }
}