<?php

declare(strict_types=1);

namespace App\Model\Admin;

use App\Model\User;
use Hyperf\DbConnection\Model\Model;

/**
 * @property int $id
 * @property string $name
 * @property int $priority
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Role extends Model
{
    /**
     * The table associated with the model.
     */
    protected ?string $table = 'role';

    /**
     * The attributes that aren't mass assignable.
     */
    protected array $guarded = ['id'];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = [];

    public function users()
    {
        return $this->hasMany(User::class, 'role_id', 'id');
    }

    public function permissions()
    {
        return $this->hasManyThrough(Permission::class, RolePermission::class, 'role_id', 'id', 'id', 'permission_id');
    }
}
