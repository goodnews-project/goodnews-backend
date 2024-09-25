<?php

declare(strict_types=1);

namespace App\Model;

use Hyperf\DbConnection\Model\Model;

/**
 * @property int $id 
 * @property int $account_id 
 * @property int $target_account_id 
 * @property array $status_ids
 * @property string $comment 
 * @property int $forward 
 * @property string $category 
 * @property array $rule_ids
 * @property array $forward_to_domains
 * @property int $assigned_account_id 
 * @property int $action_taken_by_account_id 
 * @property string $action_taken_at
 * @property-read Account|null $account
 * @property-read Account|null $targetAccount
 * @property-read Account|null $assignedAccount
 * @property-read \Hyperf\Database\Model\Collection|ReportNote[]|null $notes
 * @property string $meta
 * @property string $uri 
 * @property \Carbon\Carbon $created_at 
 * @property \Carbon\Carbon $updated_at 
 */
class Report extends Model
{
    /**
     * The table associated with the model.
     */
    protected ?string $table = 'report';

    /**
     * The attributes that aren't mass assignable.
     */
    protected array $guarded = ['id'];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['id' => 'integer', 'account_id' => 'integer', 'target_account_id' => 'integer', 'forward' => 'integer',
        'status_ids' => 'array', 'rule_ids' => 'array', 'meta' => 'array', 'forward_to_domains' => 'array', 'created_at' => 'datetime', 'updated_at' => 'datetime'];

    public function targetAccount()
    {
        return $this->hasOne(Account::class, 'id', 'target_account_id');
    }

    public function account()
    {
        return $this->hasOne(Account::class, 'id', 'account_id');
    }

    public function assignedAccount()
    {
        return $this->hasOne(Account::class, 'id', 'assigned_account_id');
    }

    public function notes()
    {
        return $this->hasMany(ReportNote::class, 'report_id', 'id');
    }

    public function logAction()
    {
        return $this->morphMany(AdminActionLog::class, 'target');
    }

    public function report()
    {
        return $this->hasMany(Report::class, 'target_account_id', 'target_account_id');
    }

}
