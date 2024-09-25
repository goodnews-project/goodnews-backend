<?php

declare(strict_types=1);

namespace App\Model;

use App\Model\Concerns\Kit;
use App\Model\Concerns\StatusWaitAttachment;
use App\Model\Scope\StatusWaitAttachmentScope;
use App\Model\StatusEdit;
use App\Service\EsService;
use App\Service\UnlockLog;
use App\Service\UrisService;
use App\Util\Lexer\Autolink;
use App\Util\Log;
use Carbon\Carbon;
use Hyperf\Database\Model\Relations\MorphMany;
use Hyperf\Database\Model\SoftDeletes;
use Hyperf\DbConnection\Model\Model;
use Hyperf\Stringable\Str;
use Richard\HyperfPassport\Client;
use function Hyperf\Support\env;

/**
 * @property int $id 
 * @property int $account_id 
 * @property int $reply_to_id 
 * @property int $reply_to_account_id 
 * @property int $reblog_id 
 * @property int $application_id
 * @property string $uri
 * @property string $url 
 * @property string $content 
 * @property string $spoiler_text
 * @property string $visibility
 * @property int $is_local
 * @property int $is_sensitive
 * @property int $comments_disabled 
 * @property int $who_can_reply
 * @property int $is_hidden_reply
 * @property int $enable_activitypub
 * @property \Carbon\Carbon $pinned_at
 * @property \Carbon\Carbon $published_at
 * @property int $fave_count
 * @property int $reply_count 
 * @property int $reblog_count 
 * @property \Carbon\Carbon $created_at 
 * @property \Carbon\Carbon $updated_at 
 * @property \Carbon\Carbon $edited_at
 * @property \Carbon\Carbon $deleted_at
 * @property int $scope
 * @property string $fee
 * @property array $emoji
 * @property-read string $content_rendered
 * @property-read Account|null $account
 * @property-read Client|null $application
 * @property-read StatusesFave|null $statusesFave
 * @property-read Bookmark|null $bookmarked 
 * @property-read Status|null $reblog 
 * @property-read Poll|null $polls
 * @property-read Mute|null $mute
 * @property-read Filter|null $filter
 * @property-read StatusUnlockLog|null $unlockLog
 * @property-read AccountSubscriberLog|null $subscriberUnlockLog
 * @property-read \Hyperf\Database\Model\Collection|StatusEdit[]|null $edits
 * @property-read \Hyperf\Database\Model\Collection|Attachment[]|null $attachments
 * @property-read \Hyperf\Database\Model\Collection|Hashtag[]|null $hashtags 
 * @property-read \Hyperf\Database\Model\Collection|Account[]|null $mentions 
 * @property-read \Hyperf\Database\Model\Collection|PreviewCard|null $previewCard
 */
class Status extends Model
{
    use Kit, SoftDeletes,StatusWaitAttachment;
    /**
     * The table associated with the model.
     */
    protected ?string $table = 'status';

    /**
     * The attributes that are mass assignable.
     */
    protected array $guarded = [];

    protected array $appends = ['content_rendered', 'emoji', 'visibility'];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = [
        'id' => 'string', 'account_id' => 'string', 'reply_to_id' => 'string', 'reply_to_account_id' => 'string',
        'reblog_id' => 'integer', 'is_local' => 'integer', 'is_sensitive' => 'integer', 'pinned_at' => 'datetime', 'comments_disabled' => 'integer',
        'who_can_reply' => 'integer', 'fave_count' => 'integer', 'reply_count' => 'integer', 'reblog_count' => 'integer', 'fee' => 'string', 'edited_at' => 'datetime:' . DATE_ISO8601,
        'created_at' => 'datetime', 'updated_at' => 'datetime', 'deleted_at' => 'datetime', 'published_at' => 'datetime:' . DATE_ISO8601, 'scope' => 'integer'
    ];

    const SCOPE_PUBLIC = 1;
    const SCOPE_PRIVATE = 2;
    const SCOPE_DIRECT = 3;
    const SCOPE_UNLISTED = 4;
    const SCOPE_MAP = [
        self::SCOPE_PUBLIC => 'public',
        self::SCOPE_PRIVATE => 'private',
        self::SCOPE_DIRECT => 'direct',
        self::SCOPE_UNLISTED => 'unlisted',
    ];


    const ES_PROPERTIES = [
        'id'           => ['type' => 'integer'],
        'account_id'   => ['type' => 'integer'],
        'content'      => ['type' => 'text'],
        'fave_count'   => ['type' => 'integer'],
        'reply_count'  => ['type' => 'integer'],
        'reblog_count' => ['type' => 'integer'],
        'created_at'   => ['type' => 'date', 'format' => 'yyyy-MM-dd HH:mm:ss'],
        'updated_at'   => ['type' => 'date', 'format' => 'yyyy-MM-dd HH:mm:ss'],
    ];

    const ES_INDEX_STATUS = 'index_status';

    const MAX_MENTIONS = 20;
    const MAX_HASHTAGS = 60;
    const MAX_LINKS = 5;

    /*
     * 谁可以回复推文
     */
    const WHO_CAN_REPLY_ALL = 0;
    const WHO_CAN_REPLY_FOLLOW = 1;
    const WHO_CAN_REPLY_MENTION = 2;

    const ENABLE_ACTIVITYPUB_ON = 1;
    const ENABLE_ACTIVITYPUB_OFF = 0;


    public function getVisibilityAttribute(): string
    {
        $scope = $this->scope ?? self::SCOPE_PUBLIC;
        return self::SCOPE_MAP[$scope];
    }

    public function getContentRenderedAttribute(): string
    {
        if (!$this->content) {
            return '';
        }
        return Autolink::create()
            ->setMentions($this->mentions)
            ->setTarget('')
            ->setBaseHashPath('/explore/hashtag/')
            ->setAutolinkActiveUsersOnly(true)
            ->autoLink($this->content);
    }

    public function getEmojiAttribute(): array
    {
        return $this->getEmoji($this->content, $this->account?->domain);
    }

    public function unlockLog()
    {
        return $this->hasOne(StatusUnlockLog::class, 'status_id', 'id');
    }

    public function subscriberUnlockLog()
    {
        return $this->hasOne(AccountSubscriberLog::class, 'target_account_id', 'account_id');
    }

    public function edits()
    {
        return $this->hasMany(StatusEdit::class, 'status_id', 'id');
    }

    public function application()
    {
        return $this->hasOne(Client::class, 'id', 'application_id');
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function statusesFave()
    {
        return $this->hasOne(StatusesFave::class);
    }

    public function bookmarked()
    {
        return $this->hasOne(Bookmark::class);
    }

    public function polls()
    {
        return $this->hasOne(Poll::class);
    }

    public function pollVote()
    {
        return $this->hasOne(PollVote::class);
    }

    public function reblog()
    {
        return $this->hasOne(Status::class, 'reblog_id', 'id');
    }

    public function attachments(): morphMany
    {
        return $this->morphMany(Attachment::class, 'attachmentable', 'from_table', 'tid');
    }

    public function mute()
    {
        return $this->hasOne(Mute::class, 'target_account_id', 'account_id');
    }

    public function originStatus()
    {
        return $this->belongsTo(Status::class, 'reblog_id', 'id');
    }

    public function scopeWithInfo($query, $account = null, $withTrashed = false)
    {
        $query->with([
            'account:id,username,display_name,avatar,domain,acct,note,profile_image,url,following_uri,followers_uri,followers_count,following_count,is_sensitive,sensitized_at,is_display_sensitive',
            'attachments',
            'mentions',
            'polls',
            'previewCard',
            'originStatus:id,content,reblog_id,account_id',
            'originStatus.account:id,username,display_name,avatar,domain,acct,note,profile_image,url,following_uri,followers_uri,followers_count,following_count',
        ]);
        $withTrashed ? $query->withTrashed() : $query->withoutTrashed();
        if ($account) {
            $query->whereDoesntHave('mute', fn ($q) => $q->where('account_id', $account['id']));
            $query->with([
                'statusesFave'             => fn ($q) => $q->where('account_id', $account['id']),
                'reblog'                   => fn ($q) => $q->where('account_id', $account['id']),
                'bookmarked'               => fn ($q) => $q->where('account_id', $account['id']),
                'account.follower'         => fn ($q) => $q->where('account_id', $account['id']),
                'mentions.follower'        => fn ($q) => $q->where('account_id', $account['id']),
                'pollVote'                 => fn ($q) => $q->where('account_id', $account['id']),
                'filter'                   => fn ($q) => $q->where('user_filter.account_id', $account['id']),
                'unlockLog'                => fn ($q) => $q->where('account_id', $account['id'])->where('state', StatusUnlockLog::STATE_UNLOCKED_Y),
                'subscriberUnlockLog'      => fn ($q) => $q->where('account_id', $account['id'])->where('expired_at', '>', Carbon::now()),
            ]);
            $query->whereDoesntHave('filter', fn ($q) => $q->where('user_filter.account_id', $account['id'])->where('filter.act', Filter::ACT_HIDE));
        }
    }

    public function loadInfo($account = null)
    {
        $this->load([
            'account:id,username,display_name,avatar,domain,acct,note,profile_image,url,following_uri,followers_uri,followers_count,following_count,is_sensitive,sensitized_at,is_display_sensitive',
            'attachments',
            'mentions',
            'polls',
            'previewCard',
            'originStatus:id,reblog_id,account_id',
            'originStatus.account:id,username,display_name,avatar,domain,acct,note,profile_image,url,following_uri,followers_uri,followers_count,following_count',
        ])->withoutTrashed();
        if ($account) {
            // $query->whereDoesntHave('mute',fn($q) => $q->where('account_id',$account['id']));
            $this->load([
                'statusesFave'             => fn ($q) => $q->where('account_id', $account['id']),
                'reblog'                   => fn ($q) => $q->where('account_id', $account['id']),
                'bookmarked'               => fn ($q) => $q->where('account_id', $account['id']),
                'account.follower'         => fn ($q) => $q->where('account_id', $account['id']),
                'mentions.follower'        => fn ($q) => $q->where('account_id', $account['id']),
                'pollVote'                 => fn ($q) => $q->where('account_id', $account['id'])
            ]);
        }
        return $this;
    }
    public function permalink($suffix = '')
    {
        return $this->account->isRemote() ? (string) $this->uri : UrisService::generateURIsForAccount($this->account->username)['statusesURI'] . '/' . $this->id . ($suffix ? '/' . $suffix : '');
    }

    public function permaurl(): ?string
    {
        return $this->account->isRemote() ? (string) $this->url : getApHostUrl() . '/User/' . $this->account->acct . '/status/' . $this->id;
    }

    public function hashtags()
    {
        return $this->hasManyThrough(
            Hashtag::class,
            StatusHashtag::class,
            'status_id',
            'id',
            'id',
            'hashtag_id'
        );
    }

    public function mentions()
    {
        return $this->hasManyThrough(
            Account::class,
            StatusesMention::class,
            'status_id',
            'id',
            'id',
            'target_account_id'
        );
    }
    public function previewCard()
    {
        return $this->hasOneThrough(
            PreviewCard::class,
            PreviewCardsStatus::class,
            'status_id',
            'id',
            'id',
            'preview_card_id'
        );
    }

    public function filter()
    {
        return $this->hasOneThrough(
            Filter::class,
            UserFilter::class,
            'status_id',
            'id',
            'id',
            'filter_id'
        );
    }

    public function parent()
    {
        $parent = $this->reply_to_id ?? $this->reblog_id;
        if (!empty($parent)) {
            return $this->findOrFail($parent);
        }
        return false;
    }

    public function saved()
    {
        if ($this->reblog_id) {
            Status::where('id', $this->reblog_id)->increment('reblog_count');
        }
    }

    public function deleted()
    {
        if ($this->reblog_id) {
            Status::where('id', $this->reblog_id)->decrement('reblog_count');
        }

        Account::where('id', $this->account_id)->where('status_count', '>', 0)?->decrement('status_count');
        if ($this->scope == self::SCOPE_DIRECT) {
            return;
        }

        //        self::getEs()->deleteDocument($this->id);
    }

    public function created()
    {
        if ($this->scope == self::SCOPE_DIRECT) {
            return;
        }

        //        self::getEs()->indexDocument($this->getEsBody($this->toArray()), $this->id);
//        Log::info('status created', $this->toArray());
    }

    public function updated()
    {
        if ($this->scope == self::SCOPE_DIRECT) {
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
//        Log::info(__CLASS__ . ' updated', compact('data', 'esData'));

        //        self::getEs()->updateDocument($this->id, $esData);
    }

    public static function getEs()
    {
        return EsService::newEs(self::ES_INDEX_STATUS);
    }

    public function reply()
    {
        return $this->belongsTo(Status::class, 'reply_to_id');
    }

    public function ancestor()
    {
        return $this->belongsTo(Status::class, 'reply_to_id');
    }
    public function descendant()
    {
        return $this->hasOne(Status::class, 'reply_to_id');
    }
}
