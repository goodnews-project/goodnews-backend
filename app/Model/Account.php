<?php

declare(strict_types=1);

namespace App\Model;

use App\Model\Concerns\Kit;
use App\Model\Concerns\Thing;
use App\Service\AccountService;
use App\Service\EsService;
use App\Service\UrisService;
use App\Util\Log;
use Carbon\Carbon;
use Hyperf\Database\Model\SoftDeletes;
use Hyperf\DbConnection\Model\Model;
use function Hyperf\Support\env;

/**
 * @property int $id 
 * @property string $username 
 * @property string $acct 
 * @property string $domain 
 * @property string $display_name
 * @property \Carbon\Carbon $suspended_at
 * @property int $is_sensitive 
 * @property string $wallet_address
 * @property int $is_display_sensitive
 * @property \Carbon\Carbon $sensitized_at
 * @property string $note
 * @property string $note_rendered
 * @property string $profile_image
 * @property string $avatar 
 * @property string $avatar_remote_url 
 * @property string $profile_remote_image
 * @property string $uri
 * @property string $url 
 * @property string $inbox_uri 
 * @property string $shared_inbox_uri 
 * @property string $outbox_uri 
 * @property string $following_uri 
 * @property string $followers_uri 
 * @property string $public_key_uri 
 * @property string $public_key 
 * @property string $private_key 
 * @property string $language 
 * @property int $followers_count 
 * @property int $following_count 
 * @property int $status_count
 * @property int $actor_type
 * @property int $is_activate 
 * @property int $manually_approves_follower
 * @property int $enable_activitypub
 * @property array $emoji
 * @property array $fields
 * @property array $settingMap
 * @property \Carbon\Carbon $last_webfingered_at
 * @property string $fee
 * @property array $subscriber_plan
 * @property int $is_long_term
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read \Hyperf\Database\Model\Collection|Follow[]|null $follows
 * @property-read \Hyperf\Database\Model\Collection|Follow[]|null $followers 
 * @property-read \Hyperf\Database\Model\Collection|Mute[]|null $mutes
 * @property-read \Hyperf\Database\Model\Collection|Block[]|null $blocks
 * @property-read \Hyperf\Database\Model\Collection|bookmark[]|null $bookmarks
 * @property-read \Hyperf\Database\Model\Collection|Report[]|null $reported
 * @property-read \Hyperf\Database\Model\Collection|AccountSubscriberLog[]|null $subscriberLogs
 * @property-read \Hyperf\Database\Model\Collection|ThingSetting[]|null $thingSetting
 * @property-read Follow|null $follower
 * @property-read User|null $user
 * @property-read \Hyperf\Database\Model\Collection|Status[]|null $tweets
 */
class Account extends Model
{
    use Kit, Thing;
    /**
     * The table associated with the model.
     */
    protected ?string $table = 'account';

    /**
     * The attributes that are mass assignable.
     */
    protected array $guarded = [];

    protected array $hidden = ['private_key'];
    protected array $appends = ['emoji','note_rendered', 'setting_map', 'is_bot', 'is_long_term', 'is_admin'];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['id' => 'string', 'is_sensitive' => 'integer', 'followers_count' => 'integer', 'following_count' => 'integer',
        'actor_type' => 'integer', 'is_activate' => 'integer', 'fields' => 'array', 'fee' => 'string', 'subscriber_plan' => 'array',
        'suspended_at' => 'datetime', 'created_at' => 'datetime:'.DATE_ATOM, 'updated_at' => 'datetime', 'last_webfingered_at' => 'datetime'];

    protected array $attributes = [
        'is_sensitive'    => false,
        'followers_count' => 0,
        'following_count' => 0,
        'actor_type'      => self::ACTOR_TYPE_PERSON,
        
    ];

    const ACTOR_TYPE_APPLICATION = 1;
    const ACTOR_TYPE_APPLICATION_STR = 'Application';

    const ACTOR_TYPE_GROUP = 2;
    const ACTOR_TYPE_GROUP_STR = 'Group';

    const ACTOR_TYPE_ORGANIZATION = 3;
    const ACTOR_TYPE_ORGANIZATION_STR = 'Organization';

    const ACTOR_TYPE_PERSON = 4;
    const ACTOR_TYPE_PERSON_STR = 'Person';

    const ACTOR_TYPE_SERVICE = 5;
    const ACTOR_TYPE_SERVICE_STR = 'Service';
    const actorTypeMap = [
        self::ACTOR_TYPE_APPLICATION_STR => self::ACTOR_TYPE_APPLICATION,
        self::ACTOR_TYPE_GROUP_STR => self::ACTOR_TYPE_GROUP,
        self::ACTOR_TYPE_ORGANIZATION_STR => self::ACTOR_TYPE_ORGANIZATION,
        self::ACTOR_TYPE_PERSON_STR => self::ACTOR_TYPE_PERSON,
        self::ACTOR_TYPE_SERVICE_STR => self::ACTOR_TYPE_SERVICE,
    ];

    const ES_PROPERTIES = [
        'id' => ['type' => 'integer'],
        'username' => ['type' => 'keyword'],
        'acct' => ['type' => 'keyword'],
        'domain' => ['type' => 'keyword'],
        'display_name' => ['type' => 'keyword'],
        'is_activate' => ['type' => 'integer'],
        'followers_count' => ['type' => 'integer'],
        'following_count' => ['type' => 'integer'],
        'created_at' => ['type' => 'date', 'format' => 'yyyy-MM-dd HH:mm:ss'],
        'updated_at' => ['type' => 'date', 'format' => 'yyyy-MM-dd HH:mm:ss'],
    ];

    const ES_INDEX_ACCOUNT = 'index_account';

    const CLIENT_WEB = 1;
    const CLIENT_APP = 2;
    const clientMap = [
        self::CLIENT_WEB => '网页端',
        self::CLIENT_APP => 'App端',
    ];

    public function getEmojiAttribute(): array
    {
        return $this->getEmoji($this->display_name, $this->domain);
    }

    public function getIsAdminAttribute(): bool
    {
        return $this->user?->role_id == 1;
    }

    public function getIsLongTermAttribute(): int
    {
        return count($this->subscriber_plan ?? []) > 1 ? 1 : 0;
    }

    public function getNoteRenderedAttribute(): string
    {
        if(!$this->note){
            return '';
        }
        return (new AccountService())->getRenderedNote($this->note);
    }

    public function getIsBotAttribute()
    {
        return $this->isBot();
    }

    public function getSettingMapAttribute()
    {
        return $this->settingMap();
    }

    public function accountWarning()
    {
        return $this->hasMany(AccountWarning::class, 'target_account_id');
    }

    public function reported()
    {
        return $this->hasMany(Report::class,'target_account_id');
    }

    public function mutes()
    {
        return $this->hasMany(Mute::class,'account_id');
    }

    public function blocks()
    {
        return $this->hasMany(Block::class,'account_id');
    }

    public function follows()
    {
        return $this->hasMany(Follow::class,'account_id');
    }
    public function followers()
    {
        return $this->hasMany(Follow::class,'target_account_id');
    } 

    public function follower()
    {
       return $this->hasOne(Follow::class,'target_account_id');
    }

    public function tweets()
    {
       return $this->hasMany(Status::class,'account_id');
    }

    public function bookmarks()
    {
        return $this->hasMany(Bookmark::class, 'account_id');
    }

    public function subscribed()
    {
        return $this->hasOne(AccountSubscriberLog::class, 'target_account_id');
    }

    public function subscriberLogs()
    {
        return $this->hasMany(AccountSubscriberLog::class, 'account_id');
    }

    public function permalink($suffix = '')
    {
        if ($suffix) {
            $suffix = str_starts_with($suffix, '#') ? $suffix : '/'. $suffix;
        }
        return $this->isRemote() ? $this->uri : UrisService::generateURIsForAccount($this->username)['userURI'].$suffix;
    }

    public function permaurl()
    {
        return $this->isRemote() ? (string) $this->url : UrisService::generateURIsForAccount($this->username)['userURL'];
    }

    public function scopeIsFollow($query,$account = null)
    {
        if($account) {
            $query->with(['follower'=>fn($q)=> $q->where('account_id',$account['id'])]);
        }    
    }

    public function scopeIsSubscribed($query,$account = null)
    {
        if($account) {
            $query->with(['subscribed'=>fn($q)=> $q->where('account_id',$account['id'])->where('expired_at', '>', Carbon::now())]);
        }
    }

    // IsLocal returns whether account is a local user account.
    public function isLocal()
    {
        return empty($this->domain) || $this->domain == env('AP_HOST');
    }

    // IsRemote returns whether account is a remote user account.
    public function isRemote()
    {
        return !$this->isLocal();
    }

    // IsInstance returns whether account is an instance internal actor account.
    public function isInstance()
    {
        if ($this->isLocal()) {
            return $this->username == env('AP_HOST');
        }

        return $this->username == $this->domain ||
            $this->followers_uri == '' ||
            $this->following_uri == '' ||
            $this->acct == 'actor';
    }

    public function isBot()
    {
        return $this->actor_type == Account::ACTOR_TYPE_SERVICE;
    }

    public function getAvatarOrDefault()
    {
        return $this->avatar ?: $this->avatar_remote_url ?: env('DEFAULT_AVATAR');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'id', 'account_id');
    }

    public function deleted()
    {
//        self::getEs()->deleteDocument($this->id);
    }

    public function created()
    {
//        self::getEs()->indexDocument($this->getEsBody($this->toArray()), $this->id);
//        Log::info('account created', $this->toArray());
    }

    public function updated()
    {
        $data = $this->getDirty();
        if (empty($data)) {
            return;
        }
        $esData = $this->getEsBody($data);
        if (empty($esData)) {
            return;
        }
//        Log::info(__CLASS__.' updated', compact('data', 'esData'));
//
//        if(!empty($data[''])){
//
//        }

//        self::getEs()->updateDocument($this->id, $esData);
    }

    public static function getEs()
    {
        return EsService::newEs(self::ES_INDEX_ACCOUNT);
    }
}
