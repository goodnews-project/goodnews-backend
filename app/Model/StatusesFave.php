<?php

declare(strict_types=1);

namespace App\Model;

use App\Nsq\Consumer\LikeConsumer;
use App\Nsq\Queue;
use Hyperf\DbConnection\Model\Model;

/**
 * @property int $id 
 * @property int $status_id 
 * @property int $account_id 
 * @property int $target_account_id 
 * @property string $uri 
 * @property \Carbon\Carbon $created_at 
 * @property \Carbon\Carbon $updated_at 
 * @property-read Account|null $account 
 * @property-read Status|null $status 
 */
class StatusesFave extends Model
{
    /**
     * The table associated with the model.
     */
    protected ?string $table = 'status_fave';

    /**
     * The attributes that are mass assignable.
     */
    protected array $guarded = ['id'];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['id' => 'integer', 'status_id' => 'integer', 'account_id' => 'integer', 'target_account_id' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime'];

    public function getByStatusIdAndAccountId($statusId, $accountId)
    {
        return self::query()->where('status_id', $statusId)->where('account_id', $accountId)->first();
    }

    public function getByStatusId($statusId)
    {
        return self::query()->where('status_id', $statusId)->get();
    }

    public function getByStatusIdWithAccount($statusId)
    {
        return self::query()->with('account')->where('status_id', $statusId)->get();
    }

    public function account()
    {
        return $this->hasOne(Account::class, 'id', 'account_id');
    }

    public function status()
    {
        return $this->hasOne(Status::class, 'id', 'status_id');
    }

    public function saved()
    {
    }
    public function created()
    {
        Status::where('id',$this->status_id)->increment('fave_count');
        $data = $this->toArray();
    }

    public function deleted()
    {
        Status::where('id',$this->status_id)->where('fave_count', '>', 0)->decrement('fave_count');
    }
}
