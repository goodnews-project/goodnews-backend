<?php
declare (strict_types=1);
namespace App\Schema;

use Hyperf\Swagger\Annotation\Property;
use Hyperf\Swagger\Annotation\Schema;
use JsonSerializable;
#[Schema(title: 'FollowSchema')]
class FollowSchema implements JsonSerializable
{
    #[Property(property: 'id', title: '', type: 'int')]
    public ?int $id;
    #[Property(property: 'account_id', title: 'Who does this follow originate from', type: 'int')]
    public ?int $accountId;
    #[Property(property: 'target_account_id', title: 'Who is the target of this follow', type: 'int')]
    public ?int $targetAccountId;
    #[Property(property: 'show_reb_logs', title: 'Does this follow also want to see reblogs and not just posts', type: 'int')]
    public ?int $showRebLogs;
    #[Property(property: 'notify', title: 'does the following account want to be notified when the followed account posts', type: 'int')]
    public ?int $notify;
    #[Property(property: 'created_at', title: '', type: 'mixed')]
    public mixed $createdAt;
    #[Property(property: 'updated_at', title: '', type: 'mixed')]
    public mixed $updatedAt;
    public function __construct(\App\Model\Follow $model)
    {
        $this->id = $model->id;
        $this->accountId = $model->account_id;
        $this->targetAccountId = $model->target_account_id;
        $this->showRebLogs = $model->show_reb_logs;
        $this->notify = $model->notify;
        $this->createdAt = $model->created_at;
        $this->updatedAt = $model->updated_at;
    }
    public function jsonSerialize() : mixed
    {
        return ['id' => $this->id, 'account_id' => $this->accountId, 'target_account_id' => $this->targetAccountId, 'show_reb_logs' => $this->showRebLogs, 'notify' => $this->notify, 'created_at' => $this->createdAt, 'updated_at' => $this->updatedAt];
    }
}