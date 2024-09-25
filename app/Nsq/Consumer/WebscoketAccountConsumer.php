<?php

declare(strict_types=1);

namespace App\Nsq\Consumer;

use App\Service\Websocket;
use Error;
use Hyperf\Codec\Packer\JsonPacker;
use Hyperf\Codec\Packer\PhpSerializerPacker;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Nsq\AbstractConsumer;
use Hyperf\Nsq\Annotation\Consumer;
use Hyperf\Nsq\Message;
use Hyperf\Nsq\Result;
use Hyperf\WebSocketServer\Sender;
use Swoole\Table;

use function Hyperf\Support\make;

#[Consumer(topic:Websocket::WEBSOCKET_ACCOUNT, channel: 'websocket', name: Websocket::WEBSOCKET_ACCOUNT, nums: 1)]
class WebscoketAccountConsumer extends AbstractConsumer
{
    #[Inject]
    protected Table $table;
    #[Inject]
    protected Sender $sender;

    public function consume(Message $payload): ?string
    {
        $packer = make(JsonPacker::class);
        $payload= $packer->unpack($payload->getBody());
        $fd = $this->table->get("ws:account:{$payload['account_id']}")['fd'] ?? null;
        if(!$this->sender->check($fd)){
            return Result::ACK;
        }

        $this->sender->push($fd,$packer->pack($payload['data']));
        
    }
}
