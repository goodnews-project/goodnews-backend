<?php

declare(strict_types=1);

namespace App\Model;

use Hyperf\DbConnection\Model\Model;

/**
 * @property int $id 
 * @property int $list_id 
 * @property int $account_id 
 * @property \Carbon\Carbon $created_at 
 * @property \Carbon\Carbon $updated_at 
 */
class ListAccount extends Model
{
    /**
     * The table associated with the model.
     */
    protected ?string $table = 'list_account';

    /**
     * The attributes that are mass assignable.
     */
    protected array $guarded = ['id'];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['id' => 'integer', 'list_id' => 'integer', 'account_id' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime'];

    public function account()
    {
       return $this->belongsTo(Account::class); 
    }
}
