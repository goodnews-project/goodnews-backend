<?php

namespace App\Nsq\Consumer;

use App\Model\Follow;
use App\Model\Notification;
use App\Nsq\Queue;
use Hyperf\Codec\Packer\PhpSerializerPacker;
use Hyperf\Context\ApplicationContext;
use Hyperf\Contract\PackerInterface;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Nsq\AbstractConsumer;
use Hyperf\Nsq\Annotation\Consumer;
use Hyperf\Nsq\Message;
use Hyperf\Nsq\Result;

#[Consumer(topic: 'queue-message', channel: 'event', name: 'queue-message-listener', nums: 1)]
class NsqQueueMessageConsumer extends AbstractConsumer
{
    public function consume(Message $message): ?string
    {
        $class = (new PhpSerializerPacker)->unpack($message->getBody());
        try{
            $class->handle();
        }catch(\Exception $e){
            var_dump($e->getMessage());
        }finally{
            return Result::ACK;
        }
    }
}
