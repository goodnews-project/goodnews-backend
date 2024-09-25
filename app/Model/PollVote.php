<?php

declare(strict_types=1);

namespace App\Model;

use Carbon\Carbon;
use Hyperf\DbConnection\Model\Model;

/**
 * @property int $id 
 * @property int $status_id 
 * @property int $account_id 
 * @property int $poll_id 
 * @property int $choice 
 * @property \Carbon\Carbon $created_at 
 * @property \Carbon\Carbon $updated_at 
 */
class PollVote extends Model
{
    /**
     * The table associated with the model.
     */
    protected ?string $table = 'poll_vote';

    /**
     * The attributes that aren't mass assignable.
     */
    protected array $guarded = ['id'];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['id' => 'integer', 'status_id' => 'integer', 'account_id' => 'integer', 'poll_id' => 'integer', 'choice' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime'];
}
