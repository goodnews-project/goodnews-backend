<?php

declare(strict_types=1);

namespace App\Model;

use Hyperf\DbConnection\Model\Model;

/**
 * @property int $id 
 * @property int $account_id 
 * @property int $filter_id 
 * @property int $status_id 
 * @property Status $status
 * @property Filter $filter
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at 
 */
class UserFilter extends Model
{
    /**
     * The table associated with the model.
     */
    protected ?string $table = 'user_filter';

    /**
     * The attributes that aren't mass assignable.
     */
    protected array $guarded = ['id'];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['id' => 'integer', 'account_id' => 'integer', 'filter_id' => 'integer', 'status_id' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime'];

    public function status()
    {
        return $this->hasOne(Status::class);
    }

    public function filter()
    {
        return $this->hasOne(Filter::class);
    }
}
