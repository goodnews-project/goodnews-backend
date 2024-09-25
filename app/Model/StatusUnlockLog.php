<?php

declare(strict_types=1);

namespace App\Model;

use Hyperf\DbConnection\Model\Model;

/**
 * @property int $id 
 * @property int $account_id 
 * @property int $target_account_id
 * @property int $status_id
 * @property string $fee
 * @property int $state 
 * @property int $pay_log_id 
 * @property \Carbon\Carbon $created_at 
 * @property \Carbon\Carbon $updated_at
 * @property-read Account|null $targetAccount
 */
class StatusUnlockLog extends Model
{
    /**
     * The table associated with the model.
     */
    protected ?string $table = 'status_unlock_log';

    /**
     * The attributes that aren't mass assignable.
     */
    protected array $guarded = ['id'];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['id' => 'integer', 'account_id' => 'integer', 'status_id' => 'integer', 'fee' => 'string', 'state' => 'integer', 'pay_log_id' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime'];

    const STATE_UNLOCKED_Y = 1;
    const STATE_UNLOCKED_N = 2;

    public function targetAccount()
    {
        return $this->hasOne(Account::class, 'id', 'target_account_id');
    }
}
