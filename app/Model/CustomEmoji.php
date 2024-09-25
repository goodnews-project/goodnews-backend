<?php

declare(strict_types=1);

namespace App\Model;

use Hyperf\DbConnection\Model\Model;

/**
 * @property \Carbon\Carbon $image_updated_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class CustomEmoji extends Model
{
    /**
     * The table associated with the model.
     */
    protected ?string $table = 'custom_emoji';

    /**
     * The attributes that aren't mass assignable.
     */
    protected array $guarded = ['id'];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['image_updated_at' => 'datetime', 'created_at' => 'datetime', 'updated_at' => 'datetime'];
}
