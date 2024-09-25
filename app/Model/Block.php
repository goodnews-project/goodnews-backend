<?php

declare(strict_types=1);

namespace App\Model;

use Hyperf\DbConnection\Model\Model;

/**
 * @property-read Account|null $targetAccount
 */
class Block extends Model
{
    /**
     * The table associated with the model.
     */
    protected ?string $table = 'block';

    protected array $guarded = ['id'];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = [];

    public function targetAccount()
    {
        return $this->hasOne(Account::class,'id','target_account_id');
    }
}
