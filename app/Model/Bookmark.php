<?php

declare(strict_types=1);

namespace App\Model;

use Hyperf\DbConnection\Model\Model;

/**
 * @property int $id 
 * @property int $status_id 
 * @property int $account_id 
 * @property \Carbon\Carbon $created_at 
 * @property \Carbon\Carbon $updated_at 
 * @property-read Status|null $status 
 * @property-read Account|null $account 
 */
class Bookmark extends Model
{
    /**
     * The table associated with the model.
     */
    protected ?string $table = 'bookmark';

    /**
     * The attributes that are mass assignable.
     */
    protected array $fillable = ['account_id', 'status_id'];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['id' => 'integer', 'status_id' => 'integer', 'account_id' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime'];

    public function status()
    {
        return $this->belongsTo(Status::class);
    }


    public function account()
    {
        return $this->belongsTo(Account::class);
    }

}
