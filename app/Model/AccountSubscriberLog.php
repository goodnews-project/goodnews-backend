<?php

declare(strict_types=1);

namespace App\Model;

use Carbon\Carbon;
use Hyperf\DbConnection\Model\Model;

/**
 * @property int $id 
 * @property int $account_id 
 * @property int $target_account_id 
 * @property int $pay_log_id 
 * @property string $fee
 * @property \Carbon\Carbon $expired_at
 * @property int $state 
 * @property int $real_state
 * @property int $plan_id
 * @property string $plan_discount
 * @property string $plan_fee
 * @property int $plan_term 
 * @property int $unread_num
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read Account|null $targetAccount
 * @property-read PayLog|null $payLog
 */
class AccountSubscriberLog extends Model
{
    /**
     * The table associated with the model.
     */
    protected ?string $table = 'account_subscriber_log';

    /**
     * The attributes that aren't mass assignable.
     */
    protected array $guarded = ['id'];

    protected array $appends = ['real_state'];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['id' => 'integer', 'account_id' => 'integer', 'target_account_id' => 'integer', 'pay_log_id' => 'integer',
        'fee' => 'string', 'state' => 'integer', 'plan_id' => 'string', 'plan_fee' => 'string', 'plan_term' => 'integer',
        'created_at' => 'datetime', 'updated_at' => 'datetime', 'expired_at' => 'datetime'];

    const STATE_SUBSCRIBED = 1; // 已订阅
    const STATE_UNSUBSCRIBED = 2; // 已取消
    const STATE_SUBSCRIBE_EXPIRED = 3; // 已过期

    public function targetAccount()
    {
        return $this->hasOne(Account::class, 'id', 'target_account_id');
    }

    public function payLog()
    {
        return $this->hasOne(PayLog::class, 'id', 'pay_log_id');
    }

    public function getRealStateAttribute(): int
    {
        if ($this->expired_at?->lt(Carbon::now())) {
            return self::STATE_SUBSCRIBE_EXPIRED;
        }
        return $this->state;
    }
}
