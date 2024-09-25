<?php

declare(strict_types=1);

namespace App\Model;

use Hyperf\DbConnection\Model\Model;

/**
 * @property int $id 
 * @property string $address 
 * @property string $account_id 
 * @property string $hash 
 * @property string $block 
 * @property int $amount 
 * @property string $withdraw_at 
 * @property \Carbon\Carbon $created_at 
 */
class RewardWithdrawLog extends Model
{
    /**
     * The table associated with the model.
     */
    protected ?string $table = 'reward_withdraw_log';

    /**
     * The attributes that are mass assignable.
     */
    protected array $guarded = ['id'];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['id' => 'integer', 'amount' => 'string', 'withdraw_at' => 'datetime:' . DATE_ATOM, 'created_at' =>  'datetime:' . DATE_ATOM,];

    const STATUS_SUCCESS = 1;
    const STATUS_PENDING = 2;
    const STATUS_FAILD = 3;
    const STATUS_TIMEOUT = 4;
}
