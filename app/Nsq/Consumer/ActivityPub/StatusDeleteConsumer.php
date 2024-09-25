<?php

declare(strict_types=1);

namespace App\Nsq\Consumer\ActivityPub;

use App\Entity\Contracts\ActivityPubActivityInterface;
use App\Model\Account;
use App\Model\Status;
use App\Nsq\Consumer\ActivityPub\Trait\ApRepository;
use App\Nsq\Consumer\BaseConsumer;
use App\Nsq\Queue;
use App\Service\Activitypub\ActivitypubService;
use App\Service\Activitypub\DeleteRemoteStatus;
use App\Util\ActivityPub\HttpSignature;
use App\Util\Log;
use Hyperf\Coroutine\Exception\ParallelExecutionException;
use Hyperf\Coroutine\Parallel;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Guzzle\ClientFactory;
use Hyperf\Nsq\Annotation\Consumer;
use Hyperf\Nsq\Result;

#[Consumer(topic: Queue::TOPIC_STATUS_DELETE, channel: Queue::CHANNEL_ACTVITYPUB, name: 'StatusConsumer', nums: 1)]
class StatusDeleteConsumer extends BaseConsumer
{
    use ApRepository;

    #[Inject]
    private ClientFactory $clientFactory;

    public function handle($data): ?string
    {
        $status = Status::withTrashed()->findOrFail($data['id']);
        if ($status->scope == Status::SCOPE_DIRECT) {
            return Result::DROP;
        }

        $account = $status->account;
        $audience = $account->followers->filter(function($item) {
            return $item->account->isRemote();
        })->map(function($item) {
            return $item->account->inbox_uri;
        })->toArray();
        if (empty($audience)) {
            Log::info('StatusDeleteConsumer have not audience', compact('audience'));
            return Result::DROP;
        }

        $apData = $this->getDeleteApData($status);

        Log::info('StatusDeleteConsumer middle', compact('apData'));

        $client = $this->clientFactory->create([
            'headers' => [
                'User-Agent' => ActivitypubService::getUa(),
            ],
            'timeout' => 15
        ]);

        $parallel = new Parallel(5);
        Log::info('StatusDeleteConsumer-audience', compact('audience'));
        foreach($audience as $url) {
            if (empty($url)) {
                continue;
            }
            $headers = HttpSignature::sign($account, $url, $apData, [
                'Content-Type'	=> 'application/activity+json; profile="'.ActivityPubActivityInterface::CONTEXT_URL.'"',
                'User-Agent'	=> ActivitypubService::getUa(),
            ]);
            Log::info('StatusDeleteConsumer to url:'.$url, compact('apData'));
            $parallel->add(function() use ($client, $url, $headers, $apData) {
                return $client->post($url, [
                    'headers' => $headers,
                    'json' => $apData
                ]);
            });
        }

        try{
            $parallel->wait();
            DeleteRemoteStatus::handle($status);
            Log::info('StatusDeleteConsumer wait end');
        } catch(ParallelExecutionException $e){
            Log::info('request exception', ['results' => $e->getMessage()]);
        }

        // send reply
        $r = $this->sendRelay($status, function ($status) {
            return $this->getDeleteApData($status);
        }, __CLASS__);
        if ($r != Result::ACK) {
            return $r;
        }

        Log::info('StatusDeleteConsumer final end');
        return Result::ACK;
    }
}
