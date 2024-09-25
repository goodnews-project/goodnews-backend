<?php

declare(strict_types=1);

namespace App\Model;

use Hyperf\DbConnection\Model\Model;

/**
 * @property int $id 
 * @property int $account_id 
 * @property int $target_account_id 
 * @property string $expires_at
 * @property-read Account|null $account
 * @property-read Account|null $targetAccount
 * @property \Carbon\Carbon $created_at 
 * @property \Carbon\Carbon $updated_at 
 */
class Mute extends Model
{
    /**
     * The table associated with the model.
     */
    protected ?string $table = 'mute';

   
    protected array $guarded = ['id'];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['id' => 'integer', 'account_id' => 'integer', 'target_account_id' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime'];

    public function account()
    {
        return $this->hasOne(Account::class, 'id', 'account_id');
    }

    public function targetAccount()
    {
        return $this->hasOne(Account::class,'id','target_account_id');
    }
}
