<?php

declare(strict_types=1);

namespace App\Model;

use App\Model\Concerns\Kit;
use App\Service\EsService;
use App\Util\Log;
use Hyperf\DbConnection\Model\Model;

/**
 * @property int $id 
 * @property string $name 
 * @property string $slug 
 * @property string $href 
 * @property int $is_sensitive 
 * @property int $is_banned 
 * @property \Carbon\Carbon $created_at 
 * @property \Carbon\Carbon $updated_at 
 */
class Hashtag extends Model
{
    use Kit;
    /**
     * The table associated with the model.
     */
    protected ?string $table = 'hashtag';

    /**
     * The attributes that are mass assignable.
     */
    protected array $guarded = ['id'];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['id' => 'integer', 'is_sensitive' => 'integer', 'is_banned' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime'];

    const MAX_TAG_LEN = 124;

    const ES_PROPERTIES = [
        'id' => ['type' => 'integer'],
        'name' => ['type' => 'keyword'],
        'slug' => ['type' => 'keyword'],
        'created_at' => ['type' => 'date', 'format' => 'yyyy-MM-dd HH:mm:ss'],
        'updated_at' => ['type' => 'date', 'format' => 'yyyy-MM-dd HH:mm:ss'],
    ];

    const ES_INDEX = 'index_hashtag';

    public function deleted()
    {
//        self::getEs()->deleteDocument($this->id);
    }

    public function created()
    {
//        self::getEs()->indexDocument($this->getEsBody($this->toArray()), $this->id);
//        Log::info(__CLASS__.' created', $this->toArray());
    }

    public function updated()
    {
        $data = $this->getDirty();
        if (empty($data)) {
            return;
        }

        $data = $this->getDirty();
        if (empty($data)) {
            return;
        }

        $esData = $this->getEsBody($data);
        if (empty($esData)) {
            return;
        }

//        Log::info(__CLASS__.' updated', compact('data', 'esData'));

//        self::getEs()->updateDocument($this->id, $this->getEsBody($data));
    }

    public static function getEs()
    {
        return EsService::newEs(self::ES_INDEX);
    }
}
