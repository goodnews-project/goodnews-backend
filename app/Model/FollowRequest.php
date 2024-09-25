<?php

declare(strict_types=1);

namespace App\Model;

use Hyperf\DbConnection\Model\Model;

/**
 * @property int $id 
 * @property int $account_id 
 * @property int $target_account_id 
 * @property array $activity
 * @property-read Account|null $account
 * @property-read Account|null $targetAccount
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at 
 */
class FollowRequest extends Model
{
    /**
     * The table associated with the model.
     */
    protected ?string $table = 'follow_request';

    /**
     * The attributes that aren't mass assignable.
     */
    protected array $guarded = ['id'];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['id' => 'integer', 'account_id' => 'integer', 'target_account_id' => 'integer', 'activity' => 'array', 'created_at' => 'datetime', 'updated_at' => 'datetime'];

    public function account()
    {
        return $this->belongsTo(Account::class, 'account_id', 'id');
    }

    public function targetAccount()
    {
        return $this->belongsTo(Account::class, 'target_account_id', 'id');
    }

    public function permalink($append = null, $namespace = '#accepts')
    {
        return $this->targetAccount->permalink("{$namespace}/follows/{$this->id}{$append}");
    }
}
