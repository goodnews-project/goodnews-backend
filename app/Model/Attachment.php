<?php

declare(strict_types=1);

namespace App\Model;

use Hyperf\Database\Model\Relations\MorphTo;
use Hyperf\DbConnection\Model\Model;

/**
 * @property int $id 
 * @property int $tid 
 * @property string $from_table 
 * @property string $url
 * @property string $remote_url
 * @property string $thumbnail_url
 * @property string $name
 * @property int $file_type 
 * @property string $type 
 * @property string $media_type 
 * @property string $blurhash 
 * @property int $width 
 * @property int $height 
 * @property int $thumbnail_width
 * @property int $thumbnail_height
 * @property int $thumbnail_file_size
 * @property int $file_size
 * @property string $focus
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at 
 */
class Attachment extends Model
{
    /**
     * The table associated with the model.
     */
    protected ?string $table = 'attachment';

    /**
     * The attributes that are mass assignable.
     */
    protected array $guarded = ['id'];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['id' => 'integer', 'tid' => 'integer', 'file_type' => 'integer', 'width' => 'integer', 'height' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime'];

    const FILE_TYPE_IMAGE = 1;
    const FILE_TYPE_GIF = 2;
    const FILE_TYPE_AUDIO = 3;
    const FILE_TYPE_VIDEO = 4;

    const STATUS_FINISH = 1;
    const STATUS_WAIT = 0;
}
