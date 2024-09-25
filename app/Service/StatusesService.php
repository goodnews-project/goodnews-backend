<?php

declare(strict_types=1);

namespace App\Service;

use App\Exception\AppException;
use App\Model\Account;
use App\Model\AccountSubscriberLog;
use App\Model\Attachment;
use App\Model\Bookmark;
use App\Model\Follow;
use App\Model\Hashtag;
use App\Model\Notification;
use App\Model\Poll;
use App\Model\PollVote;
use App\Model\PreviewCardsStatus;
use App\Model\Status;
use App\Model\StatusEdit;
use App\Model\StatusesFave;
use App\Model\StatusesMention;
use App\Model\StatusHashtag;
use App\Nsq\Consumer\ActivityPub\LikeConsumer;
use App\Nsq\Queue;
use App\Resource\Mastodon\StatusResource;
use App\Util\Lexer\Extractor;
use App\Util\Log;
use Carbon\Carbon;
use Hyperf\DbConnection\Db;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Stringable\Str;
use Hyperf\Redis\Redis;

class StatusesService
{
    #[Inject]
    private Status $status;

    #[Inject]
    private Account $account;

    #[Inject]
    private StatusesFave $statusesFave;
    #[Inject]
    protected Redis $redis;

    public function create($accountId, $content, $options)
    {
        $account = Account::find($accountId);
        $data = [
            'is_local'          => 1,
            'is_sensitive'      => $options['isSensitive'] ?? 0,
            'account_id'        => $accountId,
            'content'           => handleStatusContent($content),
            'comments_disabled' => intval($options['whoCanReply'] ?? 0 == Status::WHO_CAN_REPLY_ALL),
            'who_can_reply'     => $options['whoCanReply'] ?? 0,
            'scope'             => $options['scope'] ?? Status::SCOPE_PUBLIC,
            'published_at'      => $options['published_at'] ?? Carbon::now(),
            'spoiler_text'      => $options['spoiler_text'] ?? null,
            'application_id'    => $options['application_id'] ?? null,
            'enable_activitypub' => $options['enable_activitypub'] ?? Status::ENABLE_ACTIVITYPUB_ON,
            'fee' => $options['fee'] ?? null
        ];
        if ($data['fee'] < 0) {
            throw new AppException("金额不允许",appCode:-1);
        }

        $attachments = $options['attachments'] ?? [];

        if (!empty($options['replyToId'])) {
            $data['is_sensitive'] = ($account->sensitized_at || $account->is_sensitive) ?: $data['is_sensitive'];
        }

        if (($account->sensitized_at || $account->is_sensitive) && !$data['is_sensitive']) {
            throw new AppException("敏感用户或者设置默认发推敏感不可更改",appCode:6001);
        }

        // fix sync twitter
        if (!empty($options['id'])) {
            $data['id'] = $options['id'];
        }
        if (empty($data['content']) && empty($attachments)) {
            throw new AppException('内容不能为空');
        }

        if ($data['fee'] && empty($attachments)) {
            throw new AppException('含有图片或视频的推文才能设置费用');
        }

        if (!empty($attachments) && count($attachments) > 4) {
            throw new AppException('一次最多可提交4个文件');
        }

        if (!empty($options['replyToId'])) {
            $statusModel = Status::findOrFail($options['replyToId']);
            if ($statusModel->who_can_reply == Status::WHO_CAN_REPLY_FOLLOW) {
                $follows = $statusModel->account->follows->pluck('target_account_id')->toArray();
                if (!in_array($accountId, $follows)) {
                    Log::info('该推文仅允许作者关注的人回复', compact('accountId', 'follows'));
                    throw new AppException('该推文仅允许作者关注的人回复');
                }
            } elseif ($statusModel->who_can_reply == Status::WHO_CAN_REPLY_MENTION) {
                $mentions = $statusModel->mentions->pluck('target_account_id');
                if (!in_array($accountId, $mentions)) {
                    Log::info('该推文仅允许作者提及的人回复', compact('accountId', 'mentions'));
                    throw new AppException('该推文仅允许作者提及的人回复');
                }
            }

            $data['reply_to_id'] = $statusModel->id;
            $data['reply_to_account_id'] = $statusModel->account_id;
            $statusModel->increment('reply_count');
        }

        $status = Status::create($data);
        $data['id'] = $status->id;
        foreach ($attachments as $attachment) {
            Attachment::findOrFail($attachment['id'])->update([
                'from_table' => Status::class,
                'tid'        => $status->id,
            ]);
        }
        $account->increment('status_count');


        if ($status->content) {
            $entities = Extractor::create()->extract($status->content);
            $this->storeHashtags($status, $entities);
            if (empty($options['not_mentions'])) {
                $this->storeMentions($status, $entities);
            }

            if (empty($options['attachments']) && !empty($entities['urls'])) {
                Queue::send(
                    array_merge($entities, ['status_id' => $status->id]),
                    Queue::TOPIC_STATUS_HAS_LINKS
                );
            }
        }

        if (!empty($options['poll'])) {
            $poll = $options['poll'];
            $pollData = [
                'poll_options'   => $poll['options'],
                'cached_tallies' => array_fill(0, count($poll['options']), 0),
                'multiple'       => $poll['multiple'] ?? false,
                'expires_at'     => Carbon::now()->addSeconds($poll['expires_in'] ?? 0),
            ];
            Poll::updateOrCreate(['status_id' => $status->id, 'account_id' => $status->account_id], $pollData);
        }
        return $data;
    }

    public function detail($authAccount, $statusId)
    {
        $status = Status::withInfo($authAccount)->findOrFail($statusId);
        // 订阅账号减未读数
        if ($authAccount) {
            AccountSubscriberLog::where('account_id', $authAccount['id'])
                ->where('target_account_id', $status->account_id)->get()->each(function (AccountSubscriberLog $log) {
                    if ($log->state == AccountSubscriberLog::STATE_SUBSCRIBED) {
                        $log->where('id', $log->id)->where('unread_num', '>', 0)->decrement('unread_num');
                    }
                });
        }
        return $status;
    }

    public function fave($account, $statusId)
    {
        $status = Status::findOrFail($statusId);
        $statusesFave = StatusesFave::updateOrCreate([
            'status_id'         => $statusId,
            'account_id'        => $account['id'],
            'target_account_id' => $status['account_id'],
            'uri'               => UrisService::generateURIsForAccount($account['username'])['likedURI'],
        ]);
        $data = $statusesFave->toArray();
        $data['action'] = LikeConsumer::ACTION_LIKE;
        Queue::send($data, Queue::TOPIC_LIKE);
        return $status;
    }

    public function unFave($account, $statusId)
    {
        $status = Status::findOrFail($statusId);
        $statusesFave = StatusesFave::where([
            ['status_id', $statusId],
            ['account_id', $account['id']],
            ['target_account_id', $status['account_id'],]
        ])->first();

        if ($statusesFave) {
            $data = $statusesFave->toArray();
            $data['action'] = LikeConsumer::ACTION_UNLIKE;
            Queue::send($data, Queue::TOPIC_LIKE);

            $statusesFave->delete();
        }
        return $status;
    }

    public function reBlog($accountId, $statusId)
    {
        $status = Status::with(['attachments', 'polls', 'previewCard'])->findOrFail($statusId);
        $reblog_id = $status->reblog_id ?: $statusId;
        $newStatus = Status::firstOrCreate([
            'reblog_id'  => $reblog_id,
            'account_id' => $accountId,
        ], [
            'content'      => $status['content'],
            'is_local'     => $status['is_local'],
            'is_sensitive' => $status['is_sensitive'],
            'published_at' => Carbon::now(),
        ]);

        if ($status->attachments) {
            $attachments = $status->attachments->toArray();
            $attachments = array_map(function ($attachment) use ($newStatus) {
                $attachment['tid'] = $newStatus['id'];
                $attachment['updated_at'] = Carbon::now();
                unset($attachment['id']);
                return $attachment;
            }, $attachments);
            Attachment::insert($attachments);
        }

        if ($status->polls) {
            $oldPoll = $status->polls;
            $poll = new Poll();
            $poll->status_id = $newStatus->id;
            $poll->account_id = $newStatus->account_id;
            $poll->poll_options = $oldPoll->poll_options;
            $poll->cached_tallies = $oldPoll->cached_tallies;
            $poll->multiple = $oldPoll->multiple;
            $poll->votes_count = $oldPoll->votes_count;
            $poll->voters_count = $oldPoll->voters_count;
            $poll->last_fetched_at = $oldPoll->last_fetched_at;
            $poll->expires_at = $oldPoll->expires_at;
            $poll->save();
        }
        if ($status->previewCard) {
            PreviewCardsStatus::create([
                'preview_card_id' => $status->previewCard->id,
                'status_id'       => $newStatus->id
            ]);
        }

        Queue::send(['statusId' => $status->id, 'newStatusId' => $newStatus->id], Queue::TOPIC_REBLOG);
        return $status;
    }

    public function undoReBlog($accountId, $statusId)
    {
        $status = Status::findOrFail($statusId);
        Status::where([
            ['reblog_id', $statusId],
            ['account_id', $accountId]
        ])->first()?->delete();
        return $status;
    }

    public function statuses($account, $type = 'with_replies', $loginAccount = null, $pagesize = 10)
    {
        $status = Status::withInfo($loginAccount)->where('account_id', $account['id']);
        switch ($type) {
            case 'with_replies':
                break;
            case 'exclude_replies':
                $status = $status->whereNull('reply_to_id');
                break;
            case 'tweets':
                $status = $status->whereNull('reply_to_id');
                $status = $status->orderByDesc('pinned_at');
                break;
            case 'media':
                $status = $status->whereHas('attachments');
                break;
            case 'likes':
                $status = $status->whereHas('statusesFave', fn ($q) => $q->where('account_id', $account['id']));
                break;
            case 'pinned':
                $status = $status->whereNotNull('pinned_at')->orderByDesc('pinned_at');
                break;
            case 'exclude_reblogs':
                $status = $status->whereNull('reblog_id');
                break;
        }

        return $status->whereIn('scope', $this->getScope($loginAccount, $account))->orderByDesc('id')->paginate($pagesize);
    }

    public function getScope($loginAccount, $account): array
    {
        if (empty($loginAccount)) {
            return [Status::SCOPE_PUBLIC, Status::SCOPE_UNLISTED];
        }

        if ($loginAccount['id'] == $account['id']) {
            return [Status::SCOPE_PUBLIC, Status::SCOPE_UNLISTED, Status::SCOPE_PRIVATE];
        }

        $isFollowing = Follow::where('account_id', $loginAccount['id'])->where('target_account_id', $account['id'])->exists();
        return $isFollowing ? [Status::SCOPE_PUBLIC, Status::SCOPE_UNLISTED, Status::SCOPE_PRIVATE] : [Status::SCOPE_PUBLIC, Status::SCOPE_UNLISTED];
    }

    public function transform(Status $status)
    {
        return Status::where('id', $status->id)
            ->with(['account:id,username,display_name,domain', 'attachments'])
            ->paginate();

        //        return [
        //            'id'                        => $status->id,
        //            'uri'                       => $status->uri,
        //            'url'                       => $status->url,
        //            'in_reply_to_id'            => $status->reply_to_id,
        //            'in_reply_to_account_id'    => $status->reply_to_account_id,
        //            'content'                   => $status->content,
        //            'created_at'                => $status->created_at?->format('Y-m-d H:i:s'),
        //            'fave_count'                => $status->fave_count,
        //            'sensitive'                 => (bool) $status->is_sensitive,
        //            'mentions'                  => StatusesMention::where('status_id', $status->id)->where('account_id', $status->account_id)->get(),
        //            'reply_count'               => $status->reply_count,
        //            'local'                     => (bool) $status->is_local,
        //            'account'					=> (new UserService($status->account))->transformAccount(),
        //            'edited_at'					=> $status->updated_at?->format('Y-m-d H:i:s'),
        //        ];
    }

    public function storeUrl()
    {
    }
    public function storeHashtags($status, $entities)
    {
        $tags = array_unique($entities['hashtags']);
        foreach ($tags as $tag) {
            if (mb_strlen($tag) > Hashtag::MAX_TAG_LEN) {
                continue;
            }
            $slug = Str::slug($tag, '-', null);
            $hashtag = Hashtag::updateOrCreate(
                ['name' => $tag],
                ['slug' => $slug]
            );

            StatusHashtag::firstOrCreate(
                [
                    'status_id'  => $status->id,
                    'hashtag_id' => $hashtag->id,

                ],
                ['account_id' => $status->account_id]
            );
        }
    }

    public function storeMentions(Status $status, $entities)
    {
        $mentions = array_unique($entities['mentions']);
        foreach ($mentions as $mention) {
            if (str_starts_with($mention, '@') || str_contains($mention, '@')) {
                SearchService::query(['q' => $mention, 'resolve' => true, 'type' => 'accounts']);
                $mentioned = Account::where('acct', ltrim($mention, '@'))->first();
            } else {
                $mentioned = Account::where('username', $mention)->whereNull('domain')->first();
            }

            if (empty($mentioned) || !isset($mentioned->id)) {
                continue;
            }

            $actor = $status->account;

            $m = new StatusesMention();
            $m->status_id = $status->id;
            $m->account_id = $actor->id;
            $m->target_account_id = $mentioned->id;
            $m->href = $mentioned->permalink();
            $m->name = '@' . $mentioned->acct;
            $m->save();

            $target = $m->target_account_id;

            if ($actor->id === $target) {
                return;
            }

            Notification::firstOrCreate(
                [
                    'account_id'        => $actor->id,
                    'target_account_id' => $target,
                    'notify_type'       => Notification::NOTIFY_TYPE_MENTION,
                    'status_id'         => $status->id,
                ]
            );
        }
    }



    public function statusReplies($statusId)
    {
        $status = Status::withInfo(Auth::account())->where([
            ['reply_to_id', $statusId],
            ['scope', Status::SCOPE_PUBLIC]
        ]);
        return $status->orderByDesc('id')->paginate(10);
    }

    // 单选投票
    public function voteSingleChoice(Poll $poll, $authAccountId, $choice)
    {
        $pollVoteQ = PollVote::query();
        $pollVoteQ->where('poll_id', $poll->id)->where('account_id', $authAccountId);
        if ($poll->multiple) {
            $pollVoteQ->where('choice', $choice);
        }

        if ($pollVoteQ->exists()) {
            throw new AppException("status.already_voted");
        }

        $vote = new PollVote;
        $vote->status_id = $poll->status_id;
        $vote->account_id = $authAccountId;
        $vote->poll_id = $poll->id;
        $vote->choice = $choice;
        $vote->save();

        $poll->votes_count = $poll->votes_count + 1;
        $poll->cached_tallies = \Hyperf\Collection\collect($poll->cached_tallies)->map(function ($tally, $key) use ($choice) {
            return $choice == $key ? $tally + 1 : $tally;
        })->toArray();
        $poll->save();
        return $poll;
    }

    // 多选投票
    public function voteMultipleChoice($poll, $authAccountId, $choices)
    {
        foreach ($choices as $choice) {
            $this->voteSingleChoice($poll, $authAccountId, $choice);
        }
        return $poll;
    }

    public function bookmark($accountId, $statusId)
    {
        $status = Status::findOrFail($statusId);
        Bookmark::firstOrCreate([
            'status_id'  => $status->id,
            'account_id' => $accountId
        ]);
        return $status;
    }

    public function unBookmark($accountId, $statusId)
    {
        $status = Status::findOrFail($statusId);
        $bookmark = Bookmark::where('status_id', $status->id)
            ->where('account_id', $accountId)
            ->first();
        if ($bookmark) {
            $bookmark->delete();
        }
        return $status;
    }

    public function destroy($accountId, $statusId)
    {
        $status = Status::where('account_id', $accountId)->findOrFail($statusId);
        $status->delete();

        Queue::send(['id' => $status->id], Queue::TOPIC_STATUS_DELETE);

        // 转发的推也删除
        Status::where('reblog_id', $statusId)->get()->each(function ($item) {
            $item->delete();
            Queue::send(['id' => $item->id], Queue::TOPIC_STATUS_DELETE);
        });
        return $status;
    }


    public function context($id, $account = null)
    {
        $status = Status::findOrFail($id);
        $ancestors = [];
        $descendants = [];

        $prevStatus = $status;
        foreach (range(1, 10) as $_) {
            if (!$prevStatus->ancestor) {
                break;
            }
            array_unshift($ancestors, $prevStatus->ancestor->loadInfo($account));
            $prevStatus = $prevStatus->ancestor;
        }

        $nextStatus = $status;
        foreach (range(1, 10) as $_) {
            if (!$nextStatus->descendant) {
                break;
            }
            $descendants[] = $nextStatus->descendant->loadInfo($account);
            $nextStatus = $nextStatus->descendant;
        }

        return [$ancestors, $descendants];
    }

    public function edit(Status $status, $attributes)
    {
        if (!$status->edits->count()) {
            StatusEdit::create([
                'status_id'              => $status->id,
                'account_id'             => $status->account_id,
                'content'                => $status->content,
                'spoiler_text'           => '',
                'is_sensitive'           => $status->is_sensitive,
                'ordered_attachment_ids' => $status->attachments()->pluck('id')->toArray(),
                'created_at'             => $status->created_at
            ]);
        }
        $cleaned = isset($attributes['status']) ? handleStatusContent($attributes['status']) : null;
        $spoiler_text = isset($attributes['spoiler_text']) ? handleStatusContent($attributes['spoiler_text']) : null;
        $sensitive = $attributes['sensitive'] ?? null;
        $data = [
            'status_id'              => $status->id,
            'account_id'             => $status->account_id,
            'content'                => $cleaned,
            'spoiler_text'           => $spoiler_text,
            'is_sensitive'           => $sensitive,
            'ordered_attachment_ids' => $status->attachments->count() ? $status->attachments()->pluck('id')->toArray() : null
        ];
        if (isset($attributes['poll'])) {
            $data['poll_options'] = $attributes['poll'];
        }
        StatusEdit::create($data);
        $this->editAttachment($status, $attributes);
        $this->editStatus($status, $attributes);

        $payload = StatusResource::make($status);
        Websocket::pushNormalizePayload(Websocket::STREAM_PUBLIC_HOME, Websocket::EVENT_STATUS_UPDATE, $payload);
        Websocket::pushNormalizePayload(Websocket::STREAM_PUBLIC_LOCAL, Websocket::EVENT_STATUS_UPDATE, $payload);
        Websocket::pushNormalizePayload(Websocket::STREAM_PUBLIC_REMOTE, Websocket::EVENT_STATUS_UPDATE, $payload);
    }

    public function editAttachment(Status $status, $attributes)
    {
        $count = $status->attachments()->count();
        if ($count <= 1) {
            return;
        }

        $oids = $status->attachments()->pluck('id')->map(function ($m) {
            return (string) $m;
        });
        $nids = \Hyperf\Collection\collect($attributes['media_ids']);

        if ($oids->toArray() == $nids->toArray()) {
            return;
        }

        foreach ($oids->diff($nids)->values()->toArray() as $mid) {
            $attachment = Attachment::find($mid);
            if (!$attachment) {
                continue;
            }

            $attachment->delete();
        }
    }

    public function editStatus(Status $status, $attributes)
    {
        if (isset($attributes['status'])) {
            $cleaned = handleStatusContent($attributes['status']);
            $status->content = $cleaned;
        } else {
            $status->content = null;
        }

        if (isset($attributes['sensitive'])) {
            $status->is_sensitive = $attributes['sensitive'];
        }

        if (isset($attributes['spoiler_text'])) {
            $status->spoiler_text = handleStatusContent($attributes['spoiler_text']);
        }

        if (isset($attributes['visibility'])) {
            $scopeMap = Status::SCOPE_MAP;
            $reScopeMap = array_flip($scopeMap);
            $status->scope = $reScopeMap[$attributes['visibility']] ?? Status::SCOPE_PUBLIC;
        }

        if (isset($attributes['fee']) && $attributes['fee'] >= 0) {
            $status->fee = $attributes['fee'];
        }

        $status->edited_at = Carbon::now();

        $status->save();
    }

    public function viewStatuses(array $ids)
    {
        $now = Carbon::now();
        $currentMinutes = $now->minute;
        $minutesPlace = $currentMinutes % 10;
        if ($minutesPlace < 5) {
            $timeKey = $now->setMinute($currentMinutes - $minutesPlace)->setSecond(0)->toDateTimeString();
        } else {
            $timeKey = $now->setMinute($currentMinutes + (5 - $minutesPlace))->setSecond(0)->toDateTimeString();
        }
        foreach ($ids as $id) {
            $this->redis->zIncrBy("status:views:{$timeKey}", 1, $id);
        }
        $this->redis->sAdd("status:views-keys",$timeKey);
        
    }
}
