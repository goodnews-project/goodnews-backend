<?php

declare(strict_types=1);

namespace App\Model;

use Hyperf\DbConnection\Model\Model;

/**
 * @property int $id 
 * @property int $status_id 
 * @property int $hashtag_id 
 * @property int $account_id 
 * @property \Carbon\Carbon $created_at 
 * @property \Carbon\Carbon $updated_at 
 */
class StatusesHashtag extends Model
{
    /**
     * The table associated with the model.
     */
    protected ?string $table = 'status_hashtag';

    /**
     * The attributes that are mass assignable.
     */
    protected array $fillable = [];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['id' => 'integer', 'status_id' => 'integer', 'hashtag_id' => 'integer', 'account_id' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime'];
}
