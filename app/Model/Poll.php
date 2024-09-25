<?php

declare(strict_types=1);

namespace App\Model;

use App\Model\Concerns\Kit;
use Carbon\Carbon;
use Hyperf\DbConnection\Model\Model;

/**
 * @property int $id 
 * @property int $status_id 
 * @property int $account_id 
 * @property array $poll_options
 * @property array $cached_tallies
 * @property int $multiple 
 * @property int $hide_totals 
 * @property int $votes_count 
 * @property int $voters_count 
 * @property string $last_fetched_at 
 * @property Carbon $expires_at
 * @property int $is_expires
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at 
 * @property-read \Hyperf\Database\Model\Collection|PollVote[]|null $votes 
 */
class Poll extends Model
{
    use Kit;
    /**
     * The table associated with the model.
     */
    protected ?string $table = 'poll';

    /**
     * The attributes that aren't mass assignable.
     */
    protected array $guarded = ['id'];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['id' => 'integer', 'poll_options' => 'array', 'cached_tallies' => 'array', 'status_id' => 'integer',
        'account_id' => 'integer', 'multiple' => 'integer', 'hide_totals' => 'integer', 'votes_count' => 'integer', 'voters_count' => 'integer',
        'expires_at' => 'datetime:'.DATE_ISO8601, 'created_at' => 'datetime:'.DATE_ISO8601, 'updated_at' => 'datetime'];

    protected array $appends = ['expires_text', 'is_expires', 'percent'];

    public function votes()
    {
        return $this->hasMany(PollVote::class);
    }

    // 是否过期
    public function getIsExpiresAttribute()
    {
        if (is_null($this->expires_at)) {
            return 1;
        }

        $now = Carbon::now();
        if ($now->gt($this->expires_at)) {
            return 1;
        }
        return 0;
    }

    // 剩余时间
    public function getExpiresTextAttribute()
    {
        return $this->getRemainingTime($this->expires_at);
    }

    // 百分比
    public function getPercentAttribute()
    {
        return \Hyperf\Collection\collect($this->cached_tallies)->map(function($item) {
            if ($item <= 0 || $this->votes_count <= 0 || $item > $this->votes_count) {
                return '0%';
            }
            return round(($item / $this->votes_count) * 100) . '%';
        });
    }
}
