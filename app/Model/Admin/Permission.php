<?php

declare(strict_types=1);

namespace App\Model\Admin;

use Hyperf\DbConnection\Model\Model;

class Permission extends Model
{
    /**
     * The table associated with the model.
     */
    protected ?string $table = 'permission';

    /**
     * The attributes that aren't mass assignable.
     */
    protected array $guarded = ['id'];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = [];

    public function permissionGroup()
    {
        return $this->belongsTo(PermissionGroup::class,'group_id','id');
    }
}
