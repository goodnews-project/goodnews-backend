<?php

declare(strict_types=1);

namespace App\Model;

use Hyperf\DbConnection\Model\Model;

/**
 * @property int $id 
 * @property int $filesize 
 * @property string $file_url 
 * @property int $status
 * @property-read Account|null $account
 * @property \Carbon\Carbon $created_at 
 * @property \Carbon\Carbon $updated_at 
 */
class Export extends Model
{
    /**
     * The table associated with the model.
     */
    protected ?string $table = 'export';

    /**
     * The attributes that aren't mass assignable.
     */
    protected array $guarded = ['id'];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['id' => 'integer', 'filesize' => 'integer', 'status' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime'];

    const STATUS_EXPORTING = 1;
    const STATUS_EXPORTED = 2;

    public function account()
    {
        return $this->hasOne(Account::class, 'id', 'account_id');
    }
}
