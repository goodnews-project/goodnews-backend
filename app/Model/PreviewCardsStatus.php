<?php

declare(strict_types=1);

namespace App\Model;

use Hyperf\DbConnection\Model\Model;

/**
 * @property int $preview_card_id 
 * @property int $status_id 
 */
class PreviewCardsStatus extends Model
{
    /**
     * The table associated with the model.
     */
    protected ?string $table = 'preview_cards_status';

    /**
     * The attributes that are mass assignable.
     */
    protected array $guarded = [];

    public bool $timestamps = false;

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['preview_card_id' => 'integer', 'status_id' => 'integer'];
}
