<?php

declare(strict_types=1);

namespace App\Model\Admin;

use Hyperf\DbConnection\Model\Model;

/**
 * @property int $id 
 * @property string $ip 
 * @property int $severity 
 * @property string $expires_at 
 * @property string $comment 
 * @property \Carbon\Carbon $created_at 
 * @property \Carbon\Carbon $updated_at 
 */
class IpBlock extends Model
{
    /**
     * The table associated with the model.
     */
    protected ?string $table = 'ip_block';

    /**
     * The attributes that aren't mass assignable.
     */
    protected array $guarded = ['id'];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['id' => 'integer', 'severity' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime'];

    const severity_1 = 1;
    const severity_2 = 2;
    const severity_3 = 3;

    const EXPIRES_IN_1D = 86400;
    const EXPIRES_IN_2W = 86400 * 7 * 2;
    const EXPIRES_IN_1M = 86400 * 30;
    const EXPIRES_IN_6M = 86400 * 180;
    const EXPIRES_IN_1Y = 86400 * 365;
    const EXPIRES_IN_3Y = 86400 * 365 * 3;

    const EXPIRES_IN_MAP = [
        self::EXPIRES_IN_1D => '一天',
        self::EXPIRES_IN_2W => '两周',
        self::EXPIRES_IN_1M => '1一个月',
        self::EXPIRES_IN_6M => '6个月',
        self::EXPIRES_IN_1Y => '一年',
        self::EXPIRES_IN_3Y => '三年',
    ];

}
