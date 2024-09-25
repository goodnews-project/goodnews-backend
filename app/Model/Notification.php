<?php

declare(strict_types=1);

namespace App\Model;

use App\Resource\Mastodon\NotificationResource;
use App\Service\Websocket;
use Hyperf\Database\Model\Events\Created;
use Hyperf\DbConnection\Model\Model;

/**
 * @property int $id 
 * @property int $status_id 
 * @property int $account_id 
 * @property int $target_account_id 
 * @property int $notify_type 
 * @property int $read 
 * @property \Carbon\Carbon $created_at 
 * @property \Carbon\Carbon $updated_at 
 * @property-read Account|null $account 
 * @property-read Account|null $targetAccount 
 * @property-read Status|null $status 
 */
class Notification extends Model
{
    /**
     * The table associated with the model.
     */
    protected ?string $table = 'notification';

    /**
     * The attributes that are mass assignable.
     */
    protected array $guarded = ['id'];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['id' => 'integer', 'status_id' => 'integer', 'account_id' => 'integer', 'target_account_id' => 'integer', 'notify_type' => 'integer', 'read' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime'];

    protected array $attributes = [
        'read' => 0,
    ];

    // Type of this notification; 1:follow 2:mention 3:reblog 4:favourite 5:status 6:dm
    const NOTIFY_TYPE_FOLLOW = 1;
    const NOTIFY_TYPE_MENTION = 2;
    const NOTIFY_TYPE_REBLOG = 3;
    const NOTIFY_TYPE_FAVOURITE = 4;
    const NOTIFY_TYPE_STATUS = 5;
    const NOTIFY_TYPE_DM = 6;
    const NOTIFY_TYPE_FOLLOW_REQUEST= 8; //关注请求
    const NOTIFY_TYPE_POLL= 9; //投票结束
    const NOTIFY_TYPE_UPDATE= 10; //推文更新
    const NOTIFY_TYPE_ADMIN_SIGN_UP =11; // 用户注册
    const NOTIFY_TYPE_REPORT =12; // 用户举报
    const NOTIFY_TYPE_SYSTEM = 20; // 系统通知

    public function account()
    {
        return $this->hasOne(Account::class, 'id', 'account_id');
    }

    public function targetAccount()
    {
        return $this->hasOne(Account::class, 'id', 'target_account_id');
    }

    public function status()
    {
        return $this->hasOne(Status::class, 'id', 'status_id');
    }

    public function created()
    {
        Websocket::pushNotification(NotificationResource::make($this), $this->target_account_id);
    }
}
