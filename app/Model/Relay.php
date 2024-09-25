<?php

declare(strict_types=1);

namespace App\Model;

use Hyperf\Database\Model\SoftDeletes;
use Hyperf\DbConnection\Model\Model;

/**
 * @property int $id 
 * @property string $inbox_url 
 * @property string $follow_activity_id 
 * @property int $state 
 * @property int $mode
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at 
 * @property \Carbon\Carbon $deleted_at
 */
class Relay extends Model
{
    use SoftDeletes;
    /**
     * The table associated with the model.
     */
    protected ?string $table = 'relay';

    /**
     * The attributes that aren't mass assignable.
     */
    protected array $guarded = ['id'];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['id' => 'integer', 'state' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime', 'deleted_at' => 'datetime'];

    const STATE_IDLE = 0; // disable
    const STATE_PENDING = 1; // 等待中继服务站确认
    const STATE_ACCEPTED = 2; // enable
    const STATE_REJECTED = 3; // 被拒绝添加

    const MODE_READ_ONLY = 1;
    const MODE_WRITE_ONLY = 2;
}
