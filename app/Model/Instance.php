<?php

declare(strict_types=1);

namespace App\Model;

use Hyperf\DbConnection\Model\Model;

/**
 * @property int $id 
 * @property string $domain 
 * @property int $is_disable_download
 * @property int $is_proxy 
 * @property int $is_disable_sync
 * @property int $is_banned 
 * @property \Carbon\Carbon $created_at 
 * @property \Carbon\Carbon $updated_at 
 */
class Instance extends Model
{
    /**
     * The table associated with the model.
     */
    protected ?string $table = 'instance';

    /**
     * The attributes that aren't mass assignable.
     */
    protected array $guarded = ['id'];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['id' => 'integer', 'is_disable_download' => 'integer', 'is_proxy' => 'integer', 'is_disable_sync' => 'integer', 'is_banned' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime'];
}
