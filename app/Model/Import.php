<?php

declare(strict_types=1);

namespace App\Model;

use Hyperf\DbConnection\Model\Model;

/**
 * @property int $id 
 * @property int $type 
 * @property int $status 
 * @property int $imported_count 
 * @property int $import_total 
 * @property int $fail_count 
 * @property int $fail_file
 * @property-read Account|null $account
 * @property \Carbon\Carbon $created_at 
 * @property \Carbon\Carbon $updated_at 
 */
class Import extends Model
{
    /**
     * The table associated with the model.
     */
    protected ?string $table = 'import';

    /**
     * The attributes that aren't mass assignable.
     */
    protected array $guarded = ['id'];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['id' => 'integer', 'type' => 'integer', 'status' => 'integer', 'imported_count' => 'integer', 'import_total' => 'integer', 'fail_count' => 'integer', 'fail_file' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime'];


    const TYPE_FOLLOWING = 1;
    const TYPE_BOOKMARK = 2;
    const TYPE_MUTE = 4;
    const TYPE_BLOCK = 5;

    public function account()
    {
        return $this->hasOne(Account::class, 'id', 'account_id');
    }
}
