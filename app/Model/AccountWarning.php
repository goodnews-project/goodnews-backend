<?php

declare(strict_types=1);

namespace App\Model;

use App\Service\Auth;
use Hyperf\DbConnection\Model\Model;

/**
 * @property int $id 
 * @property int $account_id 
 * @property int $target_account_id 
 * @property int $action 
 * @property string $text 
 * @property int $report_id 
 * @property string $overruled_at 
 * @property \Carbon\Carbon $created_at 
 * @property \Carbon\Carbon $updated_at 
 */
class AccountWarning extends Model
{
    /**
     * The table associated with the model.
     */
    protected ?string $table = 'account_warning';

    /**
     * The attributes that are mass assignable.
     */
    protected array $guarded = [];
    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['id' => 'integer', 'account_id' => 'integer', 'target_account_id' => 'integer', 'action' => 'integer', 'report_id' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime'];

    const  ACTION_DISABLE = 1;
    const  ACTION_SENSITIZED = 2;
    const  ACTION_SILENCED = 3;
    const  ACTION_SUSPENDED = 4;
    const  ACTION_MARK_STATUSES_AS_SENSITIVE = 5;
    const  ACTION_DELETE_STATUS = 6;

    public function creating()
    {
        if(empty($this->account_id)){
            $this->account_id = Auth::account()['id'];
        }
    }

    public function account()
    {
        return $this->hasOne(Account::class, 'id', 'account_id');
    }


    public function targetAccount()
    {
        return $this->hasOne(Account::class,'id','target_account_id');
    }
}
