<?php

declare(strict_types=1);

namespace App\Model;

use Carbon\Carbon;
use Hyperf\DbConnection\Model\Model;

/**
 * @property int $id 
 * @property int $to_id 
 * @property int $from_id 
 * @property int $status_id 
 * @property int $dm_type 
 * @property string $read_at 
 * @property \Carbon\Carbon $created_at 
 * @property \Carbon\Carbon $updated_at 
 * @property-read Status|null $status 
 * @property-read Account|null $author 
 * @property-read Account|null $recipient 
 */
class DirectMessage extends Model
{
    /**
     * The table associated with the model.
     */
    protected ?string $table = 'direct_message';

    /**
     * The attributes that aren't mass assignable.
     */
    protected array $guarded = ['id'];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['id' => 'integer', 'to_id' => 'integer', 'from_id' => 'integer', 'status_id' => 'integer', 'dm_type' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime'];

    const DM_TYPE_TEXT = 1;
    const DM_TYPE_PHOTO = 2;
    const DM_TYPE_VIDEO = 3;

    public function status()
    {
        return $this->belongsTo(Status::class, 'status_id', 'id');
    }

    public function author()
    {
        return $this->belongsTo(Account::class, 'from_id', 'id');
    }

    public function recipient()
    {
        return $this->belongsTo(Account::class, 'to_id', 'id');
    }
}
