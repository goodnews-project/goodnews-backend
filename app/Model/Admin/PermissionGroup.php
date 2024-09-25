<?php

declare(strict_types=1);

namespace App\Model\Admin;

use Hyperf\DbConnection\Model\Model;

class PermissionGroup extends Model
{
    /**
     * The table associated with the model.
     */
    protected ?string $table = 'permission_group';

    /**
     * The attributes that aren't mass assignable.
     */
    protected array $guarded = ['id'];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = [];

    public function permissions()
    {
        return $this->hasMany(Permission::class, 'group_id', 'id');
    }
}
