<?php

namespace App\Service;

use App\Model\Follow;
use App\Model\Notification;
use App\Model\Status;
use App\Resource\Mastodon\StatusResource;
use Carbon\Carbon;
use Hyperf\Codec\Packer\JsonPacker;
use Hyperf\Codec\Packer\PhpSerializerPacker;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Nsq\Nsq;
use Hyperf\Redis\Redis;

use function Hyperf\Support\env;
use function Hyperf\Support\make;
use Hyperf\WebSocketServer\Sender;

class Websocket
{
    #[Inject]
    protected Redis $redis;
    #[Inject]
    protected Sender $sender;

    const WEBSOCKET_ANYMORE = 'websocket-anymore';
    const WEBSOCKET_ACCOUNT = 'websocket-account';

    const STREAM_USER = 'user';
    const STREAM_PUBLIC_LOCAL = 'public:local';
    const STREAM_PUBLIC_REMOTE = 'public:remote';
    const STREAM_PUBLIC_HOME = 'public:home';
    const STREAM_DIRECT = 'direct';

    const EVENT_NOTIFICATION = 'notification';
    const EVENT_UPDATE = 'update';
    const EVENT_DELETE = 'delete';
    const EVENT_STATUS_UPDATE = 'status.update';
    const EVENT_CONVERSATION = 'conversation';

    const TYPE_SUBSCRIBE = 'subscribe';
    const TYPE_UNSUBSCRIBE = 'unsubscribe';

    const CLIENT_ACCOUNT_EVENT_KEY = 'ws:account:%s:event';

    public static function publish($data, $accountId = null)
    {
        $redis = make(Redis::class);

        if (!$accountId) {
            self::sendAll($data);
            return;
        }

        $currentSubscribe = $redis->hGet("ws:account:subscribe", (string)$accountId);
        $currentSubscribe = json_decode($currentSubscribe, true);
        if ($data['stream'][0] == self::STREAM_USER) {
            self::send($accountId, $data);
            return;
        }

        if (isset($currentSubscribe['stream']) && $currentSubscribe['stream'] == $data['stream'][0]) {
            self::send($accountId, $data);
        }
    }

    public static function sendAll($data)
    {
        $redis = make(Redis::class);
        $packer = make(JsonPacker::class);
        $redis->publish("ws:broadcast", $packer->pack([
            'data' => $data
        ]));
        return true;
    }

    public static function send($accountId, $data)
    {
        $redis = make(Redis::class);

        $connect = $redis->hGet("ws:connect", (string) $accountId);
        if (empty($connect)) {
            return false;
        }
        [$serverId, $fd] = explode("_", $connect);

        $packer = make(JsonPacker::class);
        // current server
        if ($serverId == env("APP_NAME")) {
            $sender = make(Sender::class);
            $sender->push((int) $fd, $packer->pack($data));
            return true;
        }

        $redis->publish("ws:messages:{$serverId}", $packer->pack([
            'fd' => $fd,
            'data' => $data
        ]));
    }

    public static function pushNotification($payload, $accountId)
    {
        self::pushNormalizePayload(self::STREAM_USER, self::EVENT_NOTIFICATION, $payload, $accountId);
    }

    public static function pushPublicHome($payload, $accountId)
    {
        self::pushNormalizePayload(self::STREAM_PUBLIC_HOME, self::EVENT_UPDATE, $payload, $accountId);
    }

    public static function pushNormalizePayload($stream, $event, $payload, $accountId = null)
    {
        self::publish(['stream' => [$stream], 'event' => $event, 'payload' => $payload], $accountId);
    }

    public static function pushPublicLocal($payload)
    {
        self::pushNormalizePayload(self::STREAM_PUBLIC_LOCAL, self::EVENT_UPDATE, $payload);
    }

    public static function pushPublicRemote($payload)
    {
        self::pushNormalizePayload(self::STREAM_PUBLIC_REMOTE, self::EVENT_UPDATE, $payload);
    }

    public static function pushDirectMessage($payload, $accountId)
    {
        self::pushNormalizePayload(self::STREAM_DIRECT, self::EVENT_CONVERSATION, $payload, $accountId);
    }

    public static function isSubscribePublicHome($accountId): bool
    {
        return self::getAccountSubscribe($accountId)  == self::STREAM_PUBLIC_HOME;
    }

    public static function isSubscribeDirect($accountId): bool
    {
        return self::getAccountSubscribe($accountId)  == self::STREAM_DIRECT;
    }

    public static function getAccountSubscribe($accountId)
    {
        $redis = make(Redis::class);
        $subscribeJson = $redis->hGet('ws:account:subscribe', (string) $accountId);
        $subscribe = $subscribeJson ? json_decode($subscribeJson, true) : [];
        if (isset($subscribe['stream'])) {
            return $subscribe['stream'];
        }
        return false;
    }

    public static function pushStatusToFollower(Status $status)
    {
        $payload = StatusResource::make($status);
        Follow::where('target_account_id', $status->account_id)
            ->chunkById(200, function ($followAccounts) use ($status, $payload) {
                foreach ($followAccounts as $followAccount) {
                    if ($followAccount->notify == Follow::NOTIFY_ENABLE) {
                        Notification::create([
                            'account_id'        => $status->account_id,
                            'target_account_id' => $followAccount['account_id'],
                            'status_id'         => $status->id,
                            'notify_type'       => Notification::NOTIFY_TYPE_STATUS,
                            'created_at'        => Carbon::now(),
                            'updated_at'        => Carbon::now()
                        ]);
                    }

                    // 发给自己的统一到外层处理
                    if ($status->account_id == $followAccount['account_id']) {
                        continue;
                    }

                    if (!self::isSubscribePublicHome($followAccount['account_id'])) {
                        continue;
                    }

                    self::pushPublicHome($payload, $followAccount['account_id']);
                }
            });

        if (self::isSubscribePublicHome($status->account_id)) {
            self::pushPublicHome($payload, $status->account_id);
        }
    }
}
