<?php

declare(strict_types=1);

namespace App\Model;

use Hyperf\DbConnection\Model\Model;

/**
 * @property string $key 
 * @property string $type 
 * @property string $value 
 * @property \Carbon\Carbon $created_at 
 * @property \Carbon\Carbon $updated_at 
 */
class SiteConfig extends Model
{
    /**
     * The table associated with the model.
     */
    protected ?string $table = 'site_config';

    /**
     * The attributes that are mass assignable.
     */
    protected array $guarded = [];

    protected string $primaryKey = 'key';

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['created_at' => 'datetime', 'updated_at' => 'datetime'];


    
}
