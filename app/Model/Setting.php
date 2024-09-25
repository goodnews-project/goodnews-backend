<?php

declare(strict_types=1);

namespace App\Model;

use Hyperf\DbConnection\Model\Model;

/**
 * @property int $id 
 * @property string $key 
 * @property string $value 
 * @property string $settingable_type 
 * @property int $settingable_id 
 * @property \Carbon\Carbon $created_at 
 * @property \Carbon\Carbon $updated_at 
 */
class Setting extends Model
{
    /**
     * The table associated with the model.
     */
    protected ?string $table = 'setting';

    /**
     * The attributes that are mass assignable.
     */
    protected array $guarded = ['id'];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['id' => 'integer', 'settingable_id' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime'];
}
