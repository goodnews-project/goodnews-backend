<?php

declare(strict_types=1);

namespace App\Model;

use Hyperf\DbConnection\Model\Model;

/**
 * @property int $id 
 * @property string $from_address 
 * @property string $from_account_d 
 * @property string $to_address 
 * @property string $to_account 
 * @property string $hash 
 * @property string $block 
 * @property int $amount 
 * @property string $reward_at 
 * @property \Carbon\Carbon $created_at 
 */
class RewardLog extends Model
{
   /**
    * The table associated with the model.
    */
   protected ?string $table = 'reward_log';

   /**
    * The attributes that are mass assignable.
    */
   protected array $guarded = ['id'];

   /**
    * The attributes that should be cast to native types.
    */
   protected array $casts = ['id' => 'integer', 'amount' => 'string', 'reward_at' => 'datetime:' . DATE_ATOM, 'created_at' =>  'datetime:' . DATE_ATOM,];

   const UPDATED_AT = null;

   public function fromAccount()
   {
      return $this->hasOne(Account::class, 'id', 'from_account_id');
   }

   public function toAccount()
   {
      return $this->hasOne(Account::class, 'id', 'to_account_id');
   }
}
