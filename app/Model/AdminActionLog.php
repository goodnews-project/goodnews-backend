<?php

declare(strict_types=1);

namespace App\Model;

use Hyperf\DbConnection\Model\Model;

/**
 * @property int $id 
 * @property int $account_id 
 * @property string $action 
 * @property string $target_type 
 * @property int $target_id
 * @property-read Account|null $account
 * @property \Carbon\Carbon $created_at 
 * @property \Carbon\Carbon $updated_at 
 */
class AdminActionLog extends Model
{
    /**
     * The table associated with the model.
     */
    protected ?string $table = 'admin_action_log';

    /**
     * The attributes that aren't mass assignable.
     */
    protected array $guarded = ['id'];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['id' => 'integer', 'account_id' => 'integer', 'target_id' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime'];

    public function account()
    {
        return $this->hasOne(Account::class, 'id', 'account_id');
    }

}
