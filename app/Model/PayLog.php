<?php

declare(strict_types=1);

namespace App\Model;

use Hyperf\DbConnection\Model\Model;

/**
 * @property int $id 
 * @property int $account_id 
 * @property int $target_account_id
 * @property string $fee
 * @property string $send_addr 
 * @property string $recv_addr 
 * @property string $hash 
 * @property int $state
 * @property int $order_id
 * @property int $reward_type
 * @property string $block
 * @property \Carbon\Carbon $paid_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read Account|null $account
 * @property-read Account|null $targetAccount
 */
class PayLog extends Model
{
    /**
     * The table associated with the model.
     */
    protected ?string $table = 'pay_log';

    /**
     * The attributes that aren't mass assignable.
     */
    protected array $guarded = ['id'];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['id' => 'integer', 'account_id' => 'integer', 'target_account_id' => 'integer', 'reward_type' => 'integer', 'fee' => 'string',
        'created_at' => 'datetime', 'updated_at' => 'datetime', 'paid_at' => 'datetime'];

    const STATE_SUCCESS = 1;
    const STATE_PENDING = 2;
    const STATE_FAILED = 3;
    const STATE_TIMEOUT = 4;

    const TYPE_UNLOCK_STATUS = 1;
    const TYPE_SUBSCRIBE_ACCOUNT = 2;
    const TYPE_REWARD = 3;

    const REWARD_TYPE_ACCOUNT = 1;
    const REWARD_TYPE_STATUS = 2;


    public function account()
    {
        return $this->hasOne(Account::class, 'id', 'account_id');
    }

    public function targetAccount()
    {
        return $this->hasOne(Account::class, 'id', 'target_account_id');
    }
}
