<?php

declare(strict_types=1);

namespace App\Listener;

use App\Util\Log;
use Hyperf\Event\Annotation\Listener;
use Hyperf\Guzzle\ClientFactory;
use Psr\Container\ContainerInterface;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Framework\Event\BootApplication;
use Swoole\Table;
use function Hyperf\Support\env;
use function Hyperf\Support\make;

#[Listener]
class BootAppListener implements ListenerInterface
{

    public function __construct(protected ContainerInterface $container)
    {
    }

    public function listen(): array
    {
        return [
            BootApplication::class
        ];
    }

    public function process(object $event): void
    {
        $table = new Table(4096);
        $table->column('subscribe', Table::TYPE_STRING,255);
        $table->create();

        $this->container->set(Table::class,$table);

        $client = make(ClientFactory::class)->create([
            'timeout' => 15
        ]);
        try {
            $client->put('https://instance.good.news/register', ['form_params' => ['host' => env('AP_HOST')]]);
        } catch (\Exception $e) {
            Log::error('Register the instance to the good.new exception:'.$e->getMessage());
        }
    }
}
