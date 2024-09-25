<?php

declare(strict_types=1);

namespace App\Model;

use App\Model\Concerns\Kit;
use App\Model\Concerns\Thing;
use Hyperf\DbConnection\Model\Model;
use Qbhy\HyperfAuth\AuthAbility;
use Qbhy\HyperfAuth\Authenticatable;
use Richard\HyperfPassport\Auth\AuthenticatableTrait;
use Richard\HyperfPassport\HasApiTokens;

/**
 * @property int $id 
 * @property int $account_id 
 * @property string $email 
 * @property string $encrypted_password 
 * @property string $signup_ip 
 * @property \Carbon\Carbon $current_signin_at
 * @property string $current_signin_ip 
 * @property int $signin_count 
 * @property string $locale 
 * @property string $last_emailed_at 
 * @property string $confirmation_token 
 * @property string $confirmation_sent_at 
 * @property string $confirmed_at 
 * @property int $is_moderator 
 * @property int $is_admin 
 * @property int $role_id
 * @property int $is_disable
 * @property int $is_approve 
 * @property string $reset_password_token 
 * @property string $reset_password_sent_at 
 * @property \Carbon\Carbon $created_at 
 * @property \Carbon\Carbon $updated_at 
 * @property-read Account|null $account
 * @property-read \Hyperf\Database\Model\Collection|ThingSetting[]|null $thingSetting
 */
class User extends Model implements Authenticatable
{
    use AuthAbility, Kit, HasApiTokens, AuthenticatableTrait, Thing;
    /**
     * The table associated with the model.
     */
    protected ?string $table = 'user';

    /**
     * The attributes that are mass assignable.
     */
    protected array $guarded = ['id'];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['id' => 'integer', 'account_id' => 'integer', 'signin_count' => 'integer', 'is_moderator' => 'integer', 'is_admin' => 'integer', 'is_disable' => 'integer', 'is_approve' => 'integer','reset_password_sent_at'=>'datetime', 'current_signin_at' => 'datetime', 'created_at' => 'datetime', 'updated_at' => 'datetime'];
    protected array $attributes = [
        'is_moderator' => false,
        'is_admin'     => false,
        'is_disable'   => false,
        'is_approve'   => true,
        'signin_count' => 0,
        'is_moderator' => 0
    ];

    protected array $appends = ['active_time'];

    public function getActiveTimeAttribute(): string
    {
        return $this->getActiveTime($this->current_signin_at);
    }

    public function account()
    {
       return $this->belongsTo(Account::class);
    }

    public function getAuthPassword()
    {
        return $this->encrypted_password;
    }
}
