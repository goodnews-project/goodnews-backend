<?php

declare(strict_types=1);

namespace App\Model;

use App\Nsq\Consumer\FollowConsumer;
use App\Nsq\Queue;
use Hyperf\Database\Model\Events\Saved;
use Hyperf\DbConnection\Model\Model;
use Hyperf\Nsq\Nsq;

/**
 * @property int $id 
 * @property int $account_id 
 * @property int $target_account_id 
 * @property int $show_reb_logs 
 * @property int $notify 
 * @property \Carbon\Carbon $created_at 
 * @property \Carbon\Carbon $updated_at 
 * @property-read Account|null $account 
 * @property-read Account|null $targetAccount 
 */
class Follow extends Model
{
    /**
     * The table associated with the model.
     */
    protected ?string $table = 'follow';

    /**
     * The attributes that are mass assignable.
     */
    protected array $guarded = ['id'];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['id' => 'integer', 'account_id' => 'integer', 'target_account_id' => 'integer', 'show_reb_logs' => 'integer', 'notify' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime'];

    const ACTION_FOLLOW = 'follow';
    const ACTION_UNFOLLOW = 'unfollow';

    const NOTIFY_ENABLE = 1;
    const NOTIFY_DISABLE = 0;
    public function account()
    {
        return $this->hasOne(Account::class, 'id', 'account_id');
    }

    public function targetAccount()
    {
        return $this->hasOne(Account::class, 'id', 'target_account_id');
    }

    public function created()
    {
        Account::where('id',$this->account_id)->increment('following_count');
        Account::where('id',$this->target_account_id)->increment('followers_count'); 
    }

    public function saved()
    {
    }

    public function deleted()
    {
        Account::where('id',$this->account_id)->where('following_count', '>', 0)->decrement('following_count');
        Account::where('id',$this->target_account_id)->where('followers_count', '>', 0)->decrement('followers_count');
    }
}
