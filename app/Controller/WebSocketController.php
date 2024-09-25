<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\Auth;
use App\Util\Log;
use Hyperf\Codec\Packer\JsonPacker;
use Hyperf\Context\ApplicationContext;
use Hyperf\WebSocketServer\Context;
use Hyperf\Contract\OnCloseInterface;
use Hyperf\Contract\OnMessageInterface;
use Hyperf\Contract\OnOpenInterface;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Engine\WebSocket\Frame;
use Hyperf\Engine\WebSocket\Response;
use Hyperf\Redis\Redis;
use Hyperf\WebSocketServer\Constant\Opcode;
use Swoole\Server;
use Swoole\WebSocket\Server as WebSocketServer;
use Hyperf\Stringable\Str;
use Swoole\Table;

use function Hyperf\Support\env;

class WebSocketController implements OnMessageInterface, OnOpenInterface, OnCloseInterface
{
    #[Inject]
    protected Redis $redis;
    #[Inject]
    protected Table $table;
    #[Inject]
    protected JsonPacker $jsonPacker;
    public function onMessage($server, $frame): void
    {
        $response = (new Response($server))->init($frame);
        if ($frame->opcode == Opcode::PING) {
            $response->push(new Frame(opcode: Opcode::PONG));
            return;
        }

        $accountId = Context::get('ws:connect:account_id');
        $payload = $this->jsonPacker->unpack($frame->data);
        var_dump($frame->data);
        var_dump($payload);
        if (!isset($payload['type'])) {
            return;
        }


        if ($payload['type'] == "subscribe") {
            $this->redis->hSet("ws:account:subscribe", $accountId, $frame->data);
            $this->table->set((string)$response->getFd(), ["subscribe" => $payload['stream']]);
        }
        if ($payload['type'] == "unsubscribe") {
            $this->redis->hDel("ws:account:subscribe", $accountId, $frame->data);
            $this->table->del((string)$response->getFd());
        }

        if ($payload['type'] == 'heartbeat') {
            $response->push(new Frame(
                payloadData: json_encode(['event' => 'heartbeat'])
            ));
        }
    }

    public function onClose($server, int $fd, int $reactorId): void
    {
        $accountId = Context::get('ws:connect:account_id');
        Context::destroy("ws:connect:account_id");
        $this->redis->hDel("ws:connect", $accountId);
        $this->redis->hDel("ws:account:subscribe", $accountId);
        $this->table->del((string)$fd);
    }

    public function onOpen($server, $request): void
    {
        $response = (new Response($server))->init($request);

        parse_str($request->server['query_string'], $query);

        if (!isset($query['token']) || !$account = Auth::account($query['token'])) {
            $response->close();
            return;
        }
        Context::set("ws:connect:account_id", $account['id']);
        $fd = env("APP_NAME") . '_' . $request->fd;
        $this->redis->hDel("ws:account:subscribe", $account['id']);
        $this->redis->hSet("ws:connect", $account['id'], $fd);
    }
}
