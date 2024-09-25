<?php

namespace App\Util\ActivityPub;

use App\Aspect\Annotation\ExecTimeLogger;
use App\Entity\Contracts\ActivityPubActivityInterface;
use App\Exception\InboxException;
use App\Model\Account;
use App\Model\Attachment;
use App\Model\Conversation;
use App\Model\DirectMessage;
use App\Model\Follow;
use App\Model\FollowRequest;
use App\Model\Instance;
use App\Model\Notification;
use App\Model\PollVote;
use App\Model\Relay;
use App\Model\Report;
use App\Model\Status;
use App\Model\StatusesFave;

use App\Nsq\Queue;
use App\Resource\Mastodon\StatusResource;
use App\Service\Activitypub\DeleteRemoteAccount;
use App\Service\Activitypub\DeleteRemoteStatus;
use App\Service\AttachmentService;
use App\Service\AttachmentServiceV2;
use App\Service\AttachmentServiceV3;
use App\Service\RedisService;
use App\Service\UrisService;
use App\Service\UserService;
use App\Service\Websocket;
use App\Util\Image\ImageStream;
use App\Util\Log;
use Carbon\Carbon;
use Hyperf\Collection\Arr;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Utils\Str;
use Hyperf\Validation\Contract\ValidatorFactoryInterface;
use Hyperf\Validation\Rule;
use Jcupitt\Vips\Image;
use function Hyperf\Support\env;

class Inbox
{
    protected $headers;
    protected $account;
    protected $payload;
    protected $logger;

    #[Inject]
    protected ValidatorFactoryInterface $validationFactory;

    public function __construct($headers, $account, $payload)
    {
        $this->headers = $headers;
        $this->account = $account;
        $this->payload = $payload;
    }

    public function handle()
    {
        $this->handleVerb();
    }

    #[ExecTimeLogger("inbox", 'inbox')]
    public function handleVerb()
    {
        $verb = (string) $this->payload['type'];
        switch ($verb) {

            case ActivityPubActivityInterface::TYPE_CREATE:
                $this->handleCreateActivity();
                break;
            case ActivityPubActivityInterface::TYPE_ANNOUNCE:
                $validator = $this->validationFactory->make($this->payload, [
                    '@context' => 'required',
                    'id' => 'required|string',
                    'type' => [
                        'required',
                        Rule::in(['Announce'])
                    ],
                ]);
                if ($validator->fails()) {
                    // Handle exception
                    $errorMessage = $validator->errors()->first();
                    throw new \Exception($errorMessage);
                }
                $this->handleAnnounceActivity();
                break;

            case ActivityPubActivityInterface::TYPE_FOLLOW:
                $validator = $this->validationFactory->make(
                    $this->payload,
                    [
                        '@context' => 'required',
                        'id' => 'required|string',
                        'type' => [
                            'required',
                            Rule::in(['Follow'])
                        ],
                    ]
                );
                if ($validator->fails()) {
                    // Handle exception
                    $errorMessage = $validator->errors()->first();
                    throw new \Exception($errorMessage);
                }
                $this->handleFollowActivity();
                break;
            case ActivityPubActivityInterface::TYPE_ACCEPT:
                $validator = $this->validationFactory->make($this->payload, [
                    '@context' => 'required',
                    'id' => 'required|string',
                    'type' => [
                        'required',
                        Rule::in(['Accept'])
                    ],
                    'object' => 'required',
                    'object.type' => [
                        'required',
                        Rule::in(['Follow'])
                    ],
                ]);
                if ($validator->fails()) {
                    // Handle exception
                    $errorMessage = $validator->errors()->first();
                    throw new \Exception($errorMessage);
                }

                $this->handleAcceptActivity();
                break;

            case ActivityPubActivityInterface::TYPE_REJECT:
                $this->handleRejectActivity();
                break;

            case ActivityPubActivityInterface::TYPE_LIKE:
                $validator = $this->validationFactory->make(
                    $this->payload,
                    [
                        '@context' => 'required',
                        'id' => 'required|string',
                        'type' => [
                            'required',
                            Rule::in(['Like'])
                        ],
                    ]
                );
                if ($validator->fails()) {
                    // Handle exception
                    $errorMessage = $validator->errors()->first();
                    throw new \Exception($errorMessage);
                }
                $this->handleLikeActivity();
                break;
            case ActivityPubActivityInterface::TYPE_UNDO:
                $this->handleUndoActivity();
                break;
            case ActivityPubActivityInterface::TYPE_UPDATE:
                $this->handleUpdateActivity();
                break;
            case ActivityPubActivityInterface::TYPE_DELETE:
                $this->handleDeleteActivity();
                break;
            case ActivityPubActivityInterface::TYPE_Flag:
                $this->handleFlagActivity();
                break;

            default:
                return null;
        }
    }

    public function handleFlagActivity()
    {
        if (!isset(
            $this->payload['id'],
            $this->payload['type'],
            $this->payload['actor'],
            $this->payload['object']
        )) {
            return;
        }

        $id = $this->payload['id'];
        $actor = $this->payload['actor'];

        if (Helper::validateLocalUrl($id) || parse_url($id, PHP_URL_HOST) !== parse_url($actor, PHP_URL_HOST)) {
            return;
        }

        $content = null;
        if (isset($this->payload['content'])) {
            if (strlen($this->payload['content']) > 5000) {
                $content = handleStatusContent(substr($this->payload['content'], 0, 5000) . ' ... (truncated message due to exceeding max length)');
            } else {
                $content = handleStatusContent($this->payload['content']);
            }
        }
        $object = $this->payload['object'];

        if (empty($object) || (!is_array($object) && !is_string($object))) {
            return;
        }

        if (is_array($object) && count($object) > 100) {
            return;
        }

        $objects = \Hyperf\Collection\collect([]);
        $targetAccountId = null;

        foreach ($object as $objectUrl) {
            if (!Helper::validateLocalUrl($objectUrl)) {
                return;
            }

            if (str_contains($objectUrl, '/' . UrisService::UsersPath . '/')) {
                $username = Arr::last((explode('/', $objectUrl)));
                $account = Account::where('acct', $username)->first();
                $targetAccountId = $account?->id;
            } else if (str_contains($objectUrl, '/' . UrisService::StatusesPath . '/')) {
                $statusId = Arr::last(explode('/', $objectUrl));
                $objects->push($statusId);
            }
        }

        if (!$targetAccountId && !$objects->count()) {
            return;
        }

        Instance::updateOrCreate(['domain' => parse_url($id, PHP_URL_HOST)]);

        Report::create([
            'target_account_id' => $targetAccountId,
            'status_ids' => $objects->toArray(),
            'rule_ids' => null,
            'comment' => $content,
            'meta' => [
                'actor' => $actor,
                'object' => $object
            ],
            'uri' => $id
        ]);
    }

    public function handleRejectActivity()
    {
        $actor = $this->payload['actor'];
        $objActor = $this->payload['object']['actor'];
        $type = $this->payload['object']['type'];
        if ($type != ActivityPubActivityInterface::TYPE_FOLLOW) {
            return;
        }

        $actor = Helper::validateUrl($actor);
        $target = Helper::validateLocalUrl($objActor);

        if (!$actor || !$target) {
            return;
        }

        $actor = Helper::accountFetch($actor);
        $target = Helper::accountFetch($target);
        if (!$actor || !$target) {
            return;
        }

        $request = FollowRequest::where('account_id', $target->id)
            ->where('target_account_id', $actor->id)
            ->first();
        if (!$request) {
            return;
        }
        $request->delete();
    }

    public function handleAcceptActivity()
    {
        $actor = $this->payload['object']['actor'];
        $obj = $this->payload['object']['object'];
        $type = $this->payload['object']['type'];
        $id = $this->payload['object']['id'];

        if ($type != ActivityPubActivityInterface::TYPE_FOLLOW) {
            return;
        }

        if ($obj == ActivityPubActivityInterface::PUBLIC_URL) {
            $relay = Relay::where('follow_activity_id', $id)->first();
            if (!$relay) {
                return;
            }
            $relay->state = Relay::STATE_ACCEPTED;
            $relay->save();
            return;
        }

        $actor = Helper::validateLocalUrl($actor);
        $target = Helper::validateUrl($obj);

        if (!$actor || !$target) {
            return;
        }

        $actor = Helper::accountFetch($actor);
        $target = Helper::accountFetch($target);
        if (!$actor || !$target) {
            return;
        }

        $request = FollowRequest::where('account_id', $actor->id)
            ->where('target_account_id', $target->id)
            ->first();
        if (!$request) {
            return;
        }

        $follow = Follow::firstOrCreate([
            'account_id' => $actor->id,
            'target_account_id' => $target->id,
        ]);
        Queue::send($follow->toArray(), Queue::TOPIC_FOLLOW);
        $request->delete();
    }

    public function handleDeleteActivity()
    {
        if (!isset(
            $this->payload['actor'],
            $this->payload['object']
        )) {
            return;
        }
        $actor = $this->payload['actor'];
        $obj = $this->payload['object'];
        if (is_string($obj) == true && $actor == $obj && Helper::validateUrl($obj)) {
            $account = Account::where('uri', $obj)->first();
            if (!$account || $account->isLocal()) {
                return;
            }
            DeleteRemoteAccount::handle($account);
            return;
        }

        if (!isset(
            $obj['id'],
            $this->payload['object'],
            $this->payload['object']['id'],
            $this->payload['object']['type']
        )) {
            return;
        }
        $type = $this->payload['object']['type'];
        $typeCheck = in_array($type, ['Person', 'Tombstone', 'Story']);
        if (!Helper::validateUrl($actor) || !Helper::validateUrl($obj['id']) || !$typeCheck) {
            return;
        }
        if (parse_url($obj['id'], PHP_URL_HOST) !== parse_url($actor, PHP_URL_HOST)) {
            return;
        }
        $id = $this->payload['object']['id'];
        $account = Account::where('uri', $actor)->first();
        switch ($type) {
            case ActivityPubActivityInterface::TYPE_PERSON:
                if (!$account || $account->isLocal()) {
                    return;
                }
                DeleteRemoteAccount::handle($account);
                break;

            case ActivityPubActivityInterface::TYPE_TOMBSTONE:
                if (!$account || $account->isLocal()) {
                    return;
                }
                $status = Status::where('account_id', $account->id)->where('uri', $id)->first();
                if (!$status) {
                    return;
                }
                DeleteRemoteStatus::handle($status);
                break;

            default:
                break;
        }
    }

    public function handleAnnounceActivity()
    {
        $actor = $this->actorFirstOrCreate($this->payload['actor']);
        $object = $this->payload['object'];

        if (!$actor || $actor->isLocal()) {
            return;
        }

        $parent = Helper::statusFirstOrFetch($object);
        if (empty($parent)) {
            return;
        }

        $reblog_id = $parent->reblog_id ?: $parent->id;

        // todo is block
        $newStatus = Status::firstOrCreate([
            'account_id' => $actor->id,
            'reblog_id' => $reblog_id,
        ], [
            'content' => $parent->content,
            'is_local' => $parent->is_local,
            'is_sensitive' => $parent->is_sensitive,
            'published_at' => Carbon::now(),
        ]);

        $actor->status_count += 1;
        $actor->save();

        if ($parent->attachments) {
            $attachments = $parent->attachments->toArray();
            $attachments = array_map(function ($attachment) use ($newStatus) {
                $attachment['tid'] = $newStatus['id'];
                $attachment['updated_at'] = Carbon::now();
                unset($attachment['id']);
                return $attachment;
            }, $attachments);
            Attachment::insert($attachments);
        }

        Notification::firstOrCreate(
            [
                'target_account_id' => $parent->account_id,
                'account_id' => $actor->id,
                'status_id' => $parent->id,
                'notify_type' => Notification::NOTIFY_TYPE_REBLOG,
            ]
        );

        $parent->reblog_count = $parent->reblog_count + 1;
        $parent->save();
    }

    public function handleUpdateActivity()
    {
        $activity = $this->payload['object'];

        if (!isset($activity['type'], $activity['id'])) {
            return;
        }

        if (!Helper::validateUrl($activity['id'])) {
            return;
        }

        if ($activity['type'] === 'Note' && $status = Status::where('uri', $activity['id'])->first()) {
            return $this->remoteUpdateStatus($status, $activity);
        }

        if ($activity['type'] === 'Person') {
            $this->remoteUpdateAccount();
        }
    }

    public function remoteUpdateAccount()
    {
        $payload = $this->payload;

        if (empty($payload) || !isset($payload['actor'])) {
            return;
        }

        $account = Account::where('uri', $payload['actor'])->first();
        if (!$account) {
            throw new InboxException('remoteUpdateAccount:actor['.$payload['actor'].'] is null');
        }

        if ($account->isLocal()) {
            return;
        }

        if ($account->shared_inbox_uri == null || $account->shared_inbox_uri != $payload['object']['endpoints']['sharedInbox']) {
            $account->shared_inbox_uri = $payload['object']['endpoints']['sharedInbox'];
        }

        if ($account->public_key != $payload['object']['publicKey']['publicKeyPem']) {
            $account->public_key = $payload['object']['publicKey']['publicKeyPem'];
        }

        if ($account->note != $payload['object']['summary']) {
            $account->note = $payload['object']['summary'];
        }

        if ($account->display_name != $payload['object']['name']) {
            $account->display_name = $payload['object']['name'];
        }

        $account->profile_remote_image = $payload['object']['image']['url'] ?? '';
        if ($account->profile_remote_image == '') {
            $account->profile_image = '';
        } else {
            try {
                $account->profile_image = \Hyperf\Support\make(AttachmentServiceV3::class)->donwloadAndUpload($account->profile_remote_image);
                Log::info("pin pin 2");
            } catch (\Exception $e) {
                Log::error('profile_image download fail:' . $e->getMessage());
            }
        }

        $account->avatar_remote_url = $payload['object']['icon']['url'] ?? '';
        if ($account->avatar_remote_url == '') {
            $account->avatar = '';
        } else {
            try {
                $account->avatar = \Hyperf\Support\make(AttachmentServiceV3::class)->donwloadAndUpload($account->avatar_remote_url);
            } catch (\Exception $e) {
                Log::error('avatar download fail:' . $e->getMessage());
            }
        }

        $account->save();

        if (!empty($payload['object']['extra']['wallet_address'])) {
            Helper::updateAccountData($account, ['wallet_address' => $payload['object']['extra']['wallet_address']]);
        }
    }

    public function remoteUpdateStatus(Status $status, $activity)
    {
        if (isset($activity['content'])) {
            $status->content = handleStatusContent($activity['content']);
        }
        if (isset($activity['sensitive'])) {
            $status->is_sensitive = (int) $activity['sensitive'];
        }
        $status->save();

        if (!isset($activity['attachment'])) {
            return null;
        }
        if ($status->attachments?->count() == 0) {
            return null;
        }

        $instance = Instance::where('domain', $status->account->domain)->first();

        $attachments = Attachment::where('tid', $status->id)
            ->where('from_table', Status::class)
            ->get();
        \Hyperf\Support\make(AttachmentServiceV2::class)->batchDeleteWithCloud($attachments);
        \Hyperf\Collection\collect($activity['attachment'])->each(function ($attachment) use ($status, $instance) {
            if (empty($attachment['url'])) {
                return;
            }

            $file_type = array_key_exists($attachment['mediaType'], AttachmentServiceV3::VIDEOS) ? Attachment::FILE_TYPE_VIDEO : Attachment::FILE_TYPE_IMAGE;
            $data = [
                'tid' => $status->id,
                'from_table' => Status::class,
                'file_type' => $file_type,
                'remote_url' => $attachment['url'],
                'name' => $attachment['name'] ?? null,
                'type' => $attachment['type'] ?? null,
                'media_type' => $attachment['mediaType'] ?? null,
                'blurhash' => $attachment['blurhash'] ?? null,
                'width' => $attachment['width'] ?? null,
                'height' => $attachment['height'] ?? null,
                'status' => Attachment::STATUS_WAIT,
            ];

            if (empty($instance) || !$instance->is_disable_download) {
                $data['status'] = Attachment::STATUS_FINISH;
                $m = Attachment::create($data);
                \Hyperf\Support\make(AttachmentServiceV3::class)->attachmentDownload($m->id);
            } else {
                Attachment::create($data);
            }
        });

        Websocket::pushNormalizePayload(Websocket::STREAM_PUBLIC_REMOTE, Websocket::EVENT_STATUS_UPDATE, StatusResource::make($status));
        return null;
    }

    public function handleUndoActivity()
    {
        $actor = $this->payload['actor'];
        $account = self::actorFirstOrCreate($actor);
        $obj = $this->payload['object'];

        if (!$account) {
            return;
        }
        // TODO: Some implementations do not inline the object, skip for now
        if (!$obj || !is_array($obj) || !isset($obj['type'])) {
            return;
        }

        switch ($obj['type']) {
            case 'Follow':
                $following = self::actorFirstOrCreate($obj['object']);
                if (!$following) {
                    return;
                }
                Follow::where('account_id', $account->id)
                    ->where('target_account_id', $following->id)
                    ->delete();
                FollowRequest::where('account_id', $account->id)
                    ->where('target_account_id', $following->id)
                    ->delete();
                Notification::where('target_account_id', $following->id)
                    ->where('account_id', $account->id)
                    ->where('notify_type', Notification::NOTIFY_TYPE_FOLLOW)
                    ->get()
                    ->each(function ($item) {
                        $item->delete();
                    });
                break;

            case 'Like':
                $objectUri = $obj['object'];
                if (!is_string($objectUri)) {
                    if (is_array($objectUri) && isset($objectUri['id']) && is_string($objectUri['id'])) {
                        $objectUri = $objectUri['id'];
                    } else {
                        return;
                    }
                }
                $status = Helper::statusFirstOrFetch($objectUri);
                if (!$status) {
                    return;
                }
                StatusesFave::where('account_id', $account->id)
                    ->where('status_id', $status->id)
                    ->forceDelete();
                Notification::where('account_id', $status->account_id)
                    ->where('status_id', $status->id)
                    ->where('notify_type', Notification::NOTIFY_TYPE_FAVOURITE)
                    ->get()
                    ->each(function ($item) {
                        $item->delete();
                    });

                break;
        }
    }

    public function handleLikeActivity()
    {
        $actor = $this->payload['actor'];

        if (!Helper::validateUrl($actor)) {
            return;
        }

        $account = self::actorFirstOrCreate($actor);
        $obj = $this->payload['object'];
        if (!Helper::validateUrl($obj)) {
            return;
        }
        $status = Helper::statusFirstOrFetch($obj);
        if (!$status || !$account) {
            return;
        }

        $statusFave = StatusesFave::where('account_id', $account->id)->where('status_id', $status->id)->first();
        if (!$statusFave) {
            StatusesFave::create([
                'account_id' => $account->id,
                'target_account_id' => $status->account->id,
                'status_id' => $status->id
            ]);
            Notification::firstOrCreate([
                'account_id'        => $account->id,
                'target_account_id' => $status->account->id,
                'status_id'         => $status->id,
                'notify_type'       => Notification::NOTIFY_TYPE_FAVOURITE
            ]);
        }
    }

    #[ExecTimeLogger('inbox', 'inbox')]
    public function actorFirstOrCreate($actorUrl)
    {
        return Helper::accountFetch($actorUrl);
    }

    #[ExecTimeLogger("inbox", 'inbox')]
    public function handleCreateActivity()
    {
        $activity = $this->payload['object'];
        $actor = $this->actorFirstOrCreate($this->payload['actor']);
        if (!$actor) {
            return;
        }

        if (!isset($activity['to'])) {
            return;
        }
        $to = isset($activity['to']) ? $activity['to'] : [];
        $cc = isset($activity['cc']) ? $activity['cc'] : [];

        if ($activity['type'] == ActivityPubActivityInterface::TYPE_QUESTION) {
            $this->handlePollCreate();
            return;
        }

        if (
            is_array($to) &&
            is_array($cc) &&
            count($to) == 1 &&
            count($cc) == 0 &&
            parse_url($to[0], PHP_URL_HOST) == env('AP_HOST')
        ) {
            $this->handleDirectMessage();
            return;
        }

        if ($activity['type'] == 'Note' && !empty($activity['inReplyTo'])) {
            $this->handleNoteReply();
        } elseif ($activity['type'] == 'Note') {
            $this->handleNoteCreate();
        }
    }

    public function handlePollCreate()
    {
        $activity = $this->payload['object'];
        $actor = $this->actorFirstOrCreate($this->payload['actor']);
        if (!$actor || $actor->isLocal()) {
            return;
        }
        Helper::statusFirstOrFetch($activity['id']);
    }

    public function handleDirectMessage()
    {
        $activity = $this->payload['object'];
        $actor = $this->actorFirstOrCreate($this->payload['actor']);
        $toArr = explode('/', $activity['to'][0]);
        $account = Account::where('username', end($toArr))->whereNull('domain')
            ->firstOrFail();

        $msgText = strip_tags($activity['content']);

        if (str_starts_with($msgText, '@' . $account->username)) {
            $len = strlen('@' . $account->username);
            $msgText = substr($msgText, $len + 1);
        }

        if (Status::where('uri', $activity['id'])->exists()) {
            return;
        }

        $status = new Status();
        $status->account_id = $actor->id;
        $status->content = $msgText;
        $status->uri = $activity['id'];
        $status->url = $activity['url'];
        $status->reply_to_account_id = $account->id;
        $status->scope = Status::SCOPE_DIRECT;
        $status->save();

        $dm = new DirectMessage;
        $dm->to_id = $account->id;
        $dm->from_id = $actor->id;
        $dm->status_id = $status->id;
        $dm->dm_type = DirectMessage::DM_TYPE_TEXT;
        $dm->save();

        Conversation::createUniquely($actor->id, $account->id, [
            'dm_type' => $dm->dm_type,
            'status_id' => $status->id,
            'dm_id' => $dm->id,
        ]);

        if (count($activity['attachment'])) {
            $photos = 0;
            $videos = 0;
            $maxLen = 10;
            $activity['attachment'] = array_slice($activity['attachment'], 0, $maxLen);
            foreach ($activity['attachment'] as $a) {
                $mediaType = $a['mediaType'];
                $url = $a['url'];
                $valid = Helper::validateUrl($url);
                if ($valid == false) {
                    continue;
                }

                $media = new Attachment();
                $media->tid = $status->id;
                $media->from_table = Status::class;
                $media->url = $url;
                $media->name = $a['name'];
                $media->media_type = $mediaType;
                $media->type = $a['type'];
                $media->blurhash = $a['blurhash'];
                $media->width = $a['width'];
                $media->height = $a['height'];
                $media->save();
                if (explode('/', $mediaType)[0] == 'image') {
                    $photos = $photos + 1;
                }
                if (explode('/', $mediaType)[0] == 'video') {
                    $videos = $videos + 1;
                }
            }

            if ($photos && $videos == 0) {
                $dm->dm_type = DirectMessage::DM_TYPE_PHOTO;
                $dm->save();
            }
            if ($videos && $photos == 0) {
                $dm->dm_type = DirectMessage::DM_TYPE_VIDEO;
                $dm->save();
            }
        }

        if ($account->isLocal()) {
            $notification = new Notification();
            $notification->account_id = $actor->id;
            $notification->target_account_id = $account->id;
            $notification->notify_type = Notification::NOTIFY_TYPE_DM;
            $notification->status_id = $dm->status_id;
            $notification->save();
        }
    }

    public function handleNoteReply()
    {
        $activity = $this->payload['object'];
        $actor = $this->actorFirstOrCreate($this->payload['actor']);
        if (!$actor || $actor->domain == null) {
            return;
        }

        $url = isset($activity['url']) ? $activity['url'] : $activity['id'];

        Helper::statusFirstOrFetch($url, true);
    }

    #[ExecTimeLogger('inbox', 'inbox')]
    public function handleNoteCreate()
    {
        $activity = $this->payload['object'];
        $actor = $this->actorFirstOrCreate($this->payload['actor']);

        if (!$actor || $actor->isLocal()) {
            Log::info('!actor || actor->isLocal');
            return;
        }

        if (empty($activity['id'])) {
            return;
        }

        $redis = \Hyperf\Support\make(RedisService::class);
        $key = md5($activity['id']);
        if (!$redis->acquireLock($key)) {
            return;
        }

        try {

            if (Status::where('uri', $activity['id'])->exists()) {
                return;
            }

            if (
                isset($activity['inReplyTo']) &&
                isset($activity['name']) &&
                !isset($activity['content']) &&
                !isset($activity['attachment']) &&
                Helper::validateLocalUrl($activity['inReplyTo'])
            ) {
                $this->handlePollVote();
                return;
            }
            Helper::setValidationFactory($this->validationFactory);
            Helper::storeStatus(
                $actor,
                $activity
            );
            $redis->releaseLock($key);
        } catch (\Exception $e) {
            Log::error(__FUNCTION__ . ' exception:' . $e->getMessage() . ', file:' . $e->getFile() . ':' . $e->getLine() . ' , remote status id:' . $activity['id']);
            $redis->releaseLock($key);
        }
    }

    public function handlePollVote()
    {
        $activity = $this->payload['object'];
        $actor = $this->actorFirstOrCreate($this->payload['actor']);

        if (!$actor) {
            return;
        }

        $status = Helper::statusFirstOrFetch($activity['inReplyTo']);

        if (!$status) {
            return;
        }

        $poll = $status->polls;
        if (!$poll) {
            return;
        }

        if (Carbon::now()->gt($poll->expires_at)) {
            return;
        }

        $choices = $poll->poll_options;
        $choice = array_search($activity['name'], $choices);

        if ($choice === false) {
            return;
        }

        if (PollVote::where('status_id', $status->id)->where('account_id', $actor->id)->exists()) {
            return;
        }

        $vote = new PollVote;
        $vote->status_id = $status->id;
        $vote->account_id = $actor->id;
        $vote->poll_id = $poll->id;
        $vote->choice = $choice;
        $vote->save();

        $tallies = $poll->cached_tallies;
        $tallies[$choice] = $tallies[$choice] + 1;
        $poll->cached_tallies = $tallies;
        $poll->votes_count = array_sum($tallies);
        $poll->save();

        $actor->status_count += 1;
        $actor->save();
    }

    public function handleFollowActivity()
    {
        $actor = $this->actorFirstOrCreate($this->payload['actor']);
        $target = $this->actorFirstOrCreate($this->payload['object']);
        if (!$actor || !$target) {
            Log::info('actor | target domain empty');
            return;
        }

        if ($actor->domain == null || ($target->domain !== null && $target->domain != env('AP_HOST'))) {
            Log::info('actor | target domain is null!');
            return;
        }

        if (
            Follow::where('account_id', $actor->id)->where('target_account_id', $target->id)->exists()
            || FollowRequest::where('account_id', $actor->id)->where('target_account_id', $target->id)->exists()
        ) {
            Log::info('Follow exists!');
            return;
        }

        if (in_array($actor->id, $target->blocks->pluck('target_account_id')->toArray())) {
            return;
        }

        if ($target->manually_approves_follower) {
            FollowRequest::updateOrCreate([
                'account_id' => $actor->id,
                'target_account_id' => $target->id,
            ], [
                'activity' => \Hyperf\Collection\collect($this->payload)->only(['id', 'actor', 'object', 'type'])->toArray()
            ]);

            if ($target->isLocal()) {
                Notification::create([
                    'account_id' => $actor->id,
                    'target_account_id' => $target->id,
                    'notify_type' => Notification::NOTIFY_TYPE_FOLLOW_REQUEST,
                ]);
            }
            return;
        }

        $follow = Follow::updateOrCreate([
            'account_id' => $actor->id,
            'target_account_id' => $target->id,
        ]);

        // send Accept to remote account
        $accept = [
            '@context' => ActivityPubActivityInterface::CONTEXT_URL,
            'id'       => $target->permalink() . '#accepts/follows/' . $follow->id,
            'type'     => ActivityPubActivityInterface::TYPE_ACCEPT,
            'actor'    => $target->permalink(),
            'object'   => [
                'id'        => $this->payload['id'],
                'actor'     => $actor->permalink(),
                'type'      => ActivityPubActivityInterface::TYPE_FOLLOW,
                'object'    => $target->permalink()
            ]
        ];
        Helper::sendSignedObject($target, $actor->inbox_uri, $accept);

        // add to notify
        if ($target->isLocal()) {
            $notification = new Notification();
            $notification->account_id = $actor->id;
            $notification->target_account_id = $target->id;
            $notification->notify_type = Notification::NOTIFY_TYPE_FOLLOW;
            $notification->save();
        }
    }
}
