<?php

declare(strict_types=1);

namespace App\Model;

use Hyperf\DbConnection\Model\Model;

/**
 * @property int $id 
 * @property int $status_id 
 * @property int $account_id 
 * @property int $target_account_id 
 * @property string $href 
 * @property string $name 
 * @property int $silent
 * @property-read Account|null $targetAccount
 * @property \Carbon\Carbon $created_at 
 * @property \Carbon\Carbon $updated_at 
 */
class StatusesMention extends Model
{
    /**
     * The table associated with the model.
     */
    protected ?string $table = 'status_mention';

    /**
     * The attributes that are mass assignable.
     */
    protected array $guarded = ['id'];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['id' => 'integer', 'status_id' => 'integer', 'account_id' => 'integer', 'target_account_id' => 'integer', 'silent' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime'];

    public function targetAccount()
    {
        return $this->hasOne(Account::class,'id','target_account_id');
    }
}
