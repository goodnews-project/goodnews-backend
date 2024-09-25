<?php

declare(strict_types=1);

namespace App\Model;

use Hyperf\DbConnection\Model\Model;

/**
 * @property int $id 
 * @property string $var 
 * @property string $value 
 * @property int $thing_id 
 * @property string $thing_type 
 * @property \Carbon\Carbon $created_at 
 * @property \Carbon\Carbon $updated_at 
 */
class ThingSetting extends Model
{
    /**
     * The table associated with the model.
     */
    protected ?string $table = 'thing_setting';

    /**
     * The attributes that aren't mass assignable.
     */
    protected array $guarded = ['id'];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['id' => 'integer', 'thing_id' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime'];

    const VAR_DISPLAY_MEDIA = 'display_media';
    const VAR_DEFAULT_PRIVACY = 'default_privacy';
    const VAR_DEFAULT_SENSITIVE = 'default_sensitive';
    const VAR_SHOW_APPLICATION = 'show_application';
    const VAR_USE_BLURHASH = 'use_blurhash';
    const VAR_EXPAND_SPOILERS = 'expand_spoilers';
    const VAR_PUBLISH_LANGUAGE = 'publish_language';
    const VAR_FILTER_LANGUAGE = 'filter_language';

}
