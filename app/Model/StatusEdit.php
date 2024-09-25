<?php

declare(strict_types=1);

namespace App\Model;

use App\Model\Concerns\Kit;
use Hyperf\DbConnection\Model\Model;
use Hyperf\Stringable\Str;

/**
 * @property int $id
 * @property int $account_id
 * @property string $content
 * @property string $spoiler_text
 * @property int $is_sensitive
 * @property array $poll_options
 * @property array $emoji
 * @property-read Account|null $account
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class StatusEdit extends Model
{
    use Kit;
    /**
     * The table associated with the model.
     */
    protected ?string $table = 'status_edit';

    /**
     * The attributes that are mass assignable.
     */
    protected array $guarded = ['id'];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = [
        'ordered_attachment_ids' => 'array',
        'attachment_descriptions' => 'array',
        'poll_options' => 'array'
    ];

    protected array $appends = ['emoji'];

    public function getEmojiAttribute(): array
    {
        return $this->getEmoji($this->content, $this->account?->domain);
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }
}
