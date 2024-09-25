<?php

declare(strict_types=1);

namespace App\Model;

use Hyperf\DbConnection\Model\Model;

/**
 */
class CustomEmojiCategory extends Model
{
    /**
     * The table associated with the model.
     */
    protected ?string $table = 'custom_emoji_category';

    /**
     * The attributes that aren't mass assignable.
     */
    protected array $guarded = ['id'];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = [];
}
