<?php

declare(strict_types=1);

namespace App\Model;

use Hyperf\DbConnection\Model\Model;

/**
 * @property int $id 
 * @property string $content 
 * @property int $report_id 
 * @property int $account_id
 * @property-read Account|null $account
 * @property \Carbon\Carbon $created_at 
 * @property \Carbon\Carbon $updated_at 
 */
class ReportNote extends Model
{
    /**
     * The table associated with the model.
     */
    protected ?string $table = 'report_note';

    /**
     * The attributes that aren't mass assignable.
     */
    protected array $guarded = ['id'];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['id' => 'integer', 'report_id' => 'integer', 'account_id' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime'];

    public function account()
    {
        return $this->hasOne(Account::class, 'id', 'account_id');
    }
}
