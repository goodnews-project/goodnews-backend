<?php

declare(strict_types=1);

namespace App\Model;

use Hyperf\DbConnection\Model\Model;

/**
 */
class EmailDomainBlock extends Model
{
    /**
     * The table associated with the model.
     */
    protected ?string $table = 'email_domain_block';

    /**
     * The attributes that are mass assignable.
     */
    protected array $guarded = [];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = [];
}
