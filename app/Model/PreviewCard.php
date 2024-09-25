<?php

declare(strict_types=1);

namespace App\Model;

use Hyperf\DbConnection\Model\Model;

/**
 * @property int $id 
 * @property string $url 
 * @property string $title 
 * @property string $description 
 * @property string $image_url 
 * @property string $provider_name 
 * @property string $provider_url 
 * @property string $blurhash 
 * @property int $width 
 * @property int $height 
 * @property \Carbon\Carbon $created_at 
 * @property \Carbon\Carbon $updated_at 
 */
class PreviewCard extends Model
{
    /**
     * The table associated with the model.
     */
    protected ?string $table = 'preview_card';

    /**
     * The attributes that are mass assignable.
     */
    protected array $guarded = ['id'];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['id' => 'integer', 'width' => 'integer', 'height' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime'];
}
