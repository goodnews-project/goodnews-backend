<?php

declare(strict_types=1);

namespace App\Model;

use Hyperf\DbConnection\Model\Model;

/**
 * @property int $id
 * @property string $client_name
 * @property string $redirect_uris
 * @property string $scopes
 * @property string $website
 * @property string $client_id
 * @property string $client_secret
 * @property string $code
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class App extends Model
{
    /**
     * The table associated with the model.
     */
    protected ?string $table = 'Apps';

    /**
     * The attributes that aren't mass assignable.
     */
    protected array $guarded = ['id'];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = [];
}
