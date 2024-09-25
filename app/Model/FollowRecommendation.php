<?php

declare(strict_types=1);

namespace App\Model;

use Hyperf\DbConnection\Model\Model;

/**
 * @property int $id 
 * @property int $account_id 
 * @property string $rank 
 * @property string $language
 * @property int $status
 * @property \Carbon\Carbon $created_at 
 * @property \Carbon\Carbon $updated_at 
 */
class FollowRecommendation extends Model
{
    /**
     * The table associated with the model.
     */
    protected ?string $table = 'follow_recommendation';

    /**
     * The attributes that aren't mass assignable.
     */
    protected array $guarded = ['id'];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['id' => 'integer', 'account_id' => 'integer', 'status' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime'];

    const STATUS_UNSUPPRESSED = 1;
    const STATUS_SUPPRESSED = 2;

    public function account()
    {
        return $this->hasOne(Account::class, 'id', 'account_id');
    }
}
