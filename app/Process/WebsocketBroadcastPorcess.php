<?php

declare(strict_types=1);

namespace App\Process;

use Hyperf\Codec\Packer\JsonPacker;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Process\AbstractProcess;
use Hyperf\Process\Annotation\Process;
use Hyperf\Redis\Redis;
use Hyperf\Server\ServerFactory;
use Hyperf\WebSocketServer\Sender;
use Swoole\Table;

use function Hyperf\Support\env;

#[Process(name: 'WebsocketBroadcastPorcess')]
class WebsocketBroadcastPorcess extends AbstractProcess
{
    #[Inject]
    protected Redis $redis;

    #[Inject]
    protected ServerFactory $server;
    #[Inject]
    protected Sender $sender;
    #[Inject]
    protected JsonPacker $jsonPacker;
    #[Inject]
    protected Table $table;

    public function handle(): void
    {
        $redis =  $this->redis;
        $redis->setOption(\Redis::OPT_READ_TIMEOUT, -1);
        $channelName = "ws:messages:" . env("APP_NAME");
        $redis->subscribe(["ws:broadcast", $channelName], function ($redis, $channel, $msg) {
            $msg = $this->jsonPacker->unpack($msg);
            if ($channel == "ws:broadcast") {
                $this->broadcast($msg['data']);
            } else {
                $this->sender->push($msg['fd'], $this->jsonPacker->pack($msg['data']));
            }
        });
    }

    public function broadcast($msg)
    {
        $fds = $this->server->getServer()->getServer()->connections;

        foreach ($fds as $fd) {
            if (!isset($msg['stream'][0])) {
                $this->sender->push($fd, $this->jsonPacker->pack($msg));
                continue;
            }

            if ($subscribe = $this->table->get((string)$fd)) {
                if ($subscribe['subscribe'] == $msg['stream'][0]) {
                    $this->sender->push($fd, $this->jsonPacker->pack($msg));
                }
            }
        }
    }
}
