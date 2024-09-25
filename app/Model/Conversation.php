<?php

declare(strict_types=1);

namespace App\Model;

use Carbon\Carbon;
use Hyperf\DbConnection\Model\Model;

/**
 * @property int $id 
 * @property string $c_id 
 * @property int $to_id 
 * @property int $from_id 
 * @property int $dm_id 
 * @property int $status_id 
 * @property int $dm_type 
 * @property array $deleted_account
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at 
 * @property-read Account|null $fromAccount 
 * @property-read Account|null $toAccount 
 * @property-read Status|null $status 
 * @property-read DirectMessage|null $directMessage
 */
class Conversation extends Model
{
    /**
     * The table associated with the model.
     */
    protected ?string $table = 'conversation';

    /**
     * The attributes that aren't mass assignable.
     */
    protected array $guarded = ['id'];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['id' => 'integer', 'to_id' => 'integer', 'from_id' => 'integer', 'dm_id' => 'integer', 'status_id' => 'integer',
        'dm_type' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime', 'deleted_account' => 'array'];

    public function fromAccount()
    {
        return $this->hasOne(Account::class, 'id', 'from_id');
    }

    public function toAccount()
    {
        return $this->hasOne(Account::class, 'id', 'to_id');
    }

    public function status()
    {
        return $this->hasOne(Status::class, 'id', 'status_id');
    }

    public function directMessage()
    {
        return $this->hasOne(DirectMessage::class, 'id', 'dm_id');
    }

    public static function createUniquely($fromId, $toId, $data = [])
    {
        $cId = self::getCId($fromId, $toId);
        $defaultData = ['from_id' => $fromId, 'to_id' => $toId];
        if ($data) {
            $defaultData = array_merge($defaultData, $data);
        }
        $c = self::query()->where('c_id', $cId)->first();
        if (!$c) {
            return self::query()->create(array_merge(['c_id' => $cId], $defaultData));
        }
        if ($c->deleted_account) {
            if (!empty($c->deleted_account[$fromId]['deleted_at'])) {
                $c->deleted_account[$fromId]['start_dm_id'] = $defaultData['dm_id'];
                $c->deleted_account[$fromId]['state'] = 'normal';
            }

            if (!empty($c->deleted_account[$toId]['deleted_at'])) {
                $c->deleted_account[$toId]['start_dm_id'] = $defaultData['dm_id'];
                $c->deleted_account[$toId]['state'] = 'normal';
            }
        }
        $c->fill($defaultData)->save();
        return $c;
    }

    public static function getCId($fromId, $toId)
    {
        return min($fromId, $toId) . '_' . max($fromId, $toId);
    }
}
