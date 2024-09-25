<?php

declare(strict_types=1);

namespace App\Model;

use Hyperf\DbConnection\Model\Model;

/**
 * @property int $id 
 * @property string $address 
 * @property int $account_id 
 * @property \Carbon\Carbon $created_at 
 */
class WalletAddressLog extends Model
{
    /**
     * The table associated with the model.
     */
    protected ?string $table = 'wallet_address_log';

    /**
     * The attributes that are mass assignable.
     */
    protected array $guarded = ['id'];

    const UPDATED_AT = null;
    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['id' => 'integer', 'account_id' => 'integer', 'created_at' => 'datetime'];
}
