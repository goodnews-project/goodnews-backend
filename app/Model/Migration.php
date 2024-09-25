<?php

declare(strict_types=1);

namespace App\Model;

use Hyperf\DbConnection\Model\Model;

/**
 * @property int $id 
 * @property string $migration 
 * @property int $batch 
 */
class Migration extends Model
{
    /**
     * The table associated with the model.
     */
    protected ?string $table = 'migrations';

    /**
     * The attributes that are mass assignable.
     */
    protected array $guarded = ['id'];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['id' => 'integer', 'batch' => 'integer'];
}
