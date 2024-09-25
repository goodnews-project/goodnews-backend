<?php

declare(strict_types=1);

namespace App\Model;

use Carbon\Carbon;
use Hyperf\DbConnection\Model\Model;

/**
 * @property int $id 
 * @property string $title
 * @property array $context
 * @property array $context_fmt
 * @property array $context_enum
 * @property int $act
 * @property int $expires_in
 * @property array $kw_attr
 * @property \Carbon\Carbon $expired_at
 * @property \Carbon\Carbon $created_at 
 * @property \Carbon\Carbon $updated_at 
 */
class Filter extends Model
{
    /**
     * The table associated with the model.
     */
    protected ?string $table = 'filter';

    /**
     * The attributes that aren't mass assignable.
     */
    protected array $guarded = ['id'];

    protected array $appends = ['expired_at_fmt', 'context_fmt', 'context_enum'];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['id' => 'integer', 'expired_at' => 'datetime', 'act' => 'integer', 'expires_in' => 'integer', 'context' => 'array', 'kw_attr' => 'array', 'created_at' => 'datetime', 'updated_at' => 'datetime'];

    const CONTEXT_HOME = 1;
    const CONTEXT_NOTIFICATIONS = 2;
    const CONTEXT_PUBLIC = 3;
    const CONTEXT_THREAD = 4;
    const CONTEXT_ACCOUNT = 5;
    const contextMap = [
        self::CONTEXT_HOME => ['type' => 'home', 'label' => '主页时间轴'],
        self::CONTEXT_NOTIFICATIONS => ['type' => 'notifications', 'label' => '通知'],
        self::CONTEXT_PUBLIC => ['type' => 'public', 'label' => '公共时间轴'],
        self::CONTEXT_THREAD => ['type' => 'thread', 'label' => '对话'],
        self::CONTEXT_ACCOUNT => ['type' => 'account', 'label' => '个人资料'],
    ];

    const ACT_WARN = 1;
    const ACT_HIDE = 2;
    const actMap = [
        self::ACT_WARN => 'warn',
        self::ACT_HIDE => 'hide',
    ];

    public function getContextFmtAttribute(): array
    {
        return \Hyperf\Collection\collect($this->context)->transform(function ($context) {
            return ['context' => $context, 'context_name' => self::contextMap[$context]['label']];
        })->all();
    }

    public function getContextEnumAttribute(): array
    {
        return \Hyperf\Collection\collect($this->context)->transform(function ($context) {
            return self::contextMap[$context]['type'];
        })->all();
    }

    public function getExpiredAtFmtAttribute()
    {
        $expiredAt = $this->expired_at;
        if (empty($expiredAt)) {
            return '永不过期';
        }

        $now = Carbon::now();
        $interval = $now->diff($expiredAt);

        $days = $interval->format('%a');
        $hours = $interval->format('%h');
        $minutes = $interval->format('%i');

        if ($days > 7) {
            return '1周后';
        }

        if ($days > 0) {
            return '1天后';
        }

        if ($hours >= 12) {
            return '12小时后';
        }

        if ($hours >= 6) {
            return '6小时后';
        }

        if ($hours >= 1) {
            return '1小时后';
        }

        return $minutes.'分钟后';
    }

    public function scopeDistinctCountStatus($query)
    {
        return $query->selectRaw('*,(select count(distinct user_filter.status_id) from `status` inner join `user_filter` on `user_filter`.`status_id` = `status`.`id` where `filter`.`id` = `user_filter`.`filter_id`) as `status_count`');
    }
}
