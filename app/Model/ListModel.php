<?php

declare(strict_types=1);

namespace App\Model;

use Hyperf\DbConnection\Model\Model;

class ListModel extends Model
{
    /**
     * The table associated with the model.
     */
    protected ?string $table = 'list';

    /**
     * The connection name for the model.
     */
    protected ?string $connection = 'default';

    /**
     * The attributes that are mass assignable.
     */
    protected array $guarded = ['id'];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = [];
}
