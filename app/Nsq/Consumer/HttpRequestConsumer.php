<?php

declare(strict_types=1);

namespace App\Nsq\Consumer;


use App\Nsq\Queue;
use App\Service\DeliveryFailureTracker;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Guzzle\ClientFactory;
use Hyperf\Nsq\Annotation\Consumer;
use Hyperf\Nsq\Result;
use Throwable;

use function Hyperf\Support\make;

#[Consumer(topic: Queue::TOPIC_HTTP_REQUEST, channel: 'event', name: 'HttpRequestConsumer', nums: 5)]
class HttpRequestConsumer extends BaseConsumer
{
    #[Inject]
    protected ClientFactory $clientFactory;

    public function handle($message): ?string
    {
        $client = $this->clientFactory->create([
            'timeout' => 15
        ]);
        foreach ($message as $item) {
            $url = $item['url'];
            $headers = $item['headers'];
            $body = $item['body'];

            try {
                $r = $client->post($url, [
                    'headers' => $headers,
                    'json'    => $body
                ]);
            } catch (Throwable $e) {
                make(DeliveryFailureTracker::class, ['urlOrHost' => $url])->trackSuccess();
                continue;
            }

            make(DeliveryFailureTracker::class, ['urlOrHost' => $url])->trackFailure();
        }

        return Result::ACK;
    }
}
