<?php

declare(strict_types=1);

namespace App\Model;

use Hyperf\DbConnection\Model\Model;

/**
 * @property int $id 
 * @property int $account_id 
 * @property int $hashtag_id 
 * @property \Carbon\Carbon $created_at 
 * @property \Carbon\Carbon $updated_at 
 */
class FollowHashtag extends Model
{
    /**
     * The table associated with the model.
     */
    protected ?string $table = 'follow_hashtag';

    /**
     * The attributes that aren't mass assignable.
     */
    protected array $guarded = ['id'];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['id' => 'integer', 'account_id' => 'integer', 'hashtag_id' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime'];
}
