<?php

declare(strict_types=1);

namespace App\Nsq\Consumer\ActivityPub;

use App\Entity\Contracts\ActivityPubActivityInterface;
use App\Model\Follow;
use App\Model\Notification;
use App\Model\Status;
use App\Nsq\Consumer\ActivityPub\Trait\ApRepository;
use App\Nsq\Consumer\BaseConsumer;
use App\Nsq\Queue;
use App\Service\Activitypub\ActivitypubService;
use App\Util\ActivityPub\Helper;
use App\Util\ActivityPub\HttpSignature;
use App\Util\Log;
use GuzzleHttp\Client;
use Hyperf\Coroutine\Exception\ParallelExecutionException;
use Hyperf\Coroutine\Parallel;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Guzzle\ClientFactory;
use Hyperf\Nsq\Annotation\Consumer;
use Hyperf\Nsq\Result;

#[Consumer(topic: Queue::TOPIC_REBLOG, channel: Queue::CHANNEL_ACTVITYPUB, name: 'ReblogConsumer', nums: 1)]
class ReblogConsumer extends BaseConsumer
{
    use ApRepository;
    #[Inject]
    private ClientFactory $clientFactory;

    public function handle($message)
    {

        $status = Status::findOrFail($message['newStatusId']);
        $parent = Status::findOrFail($status->reblog_id);
        if(!$parent) {
            return Result::DROP;
        }
        $actor = $status->account;
        $target = $parent->account;

        if ($actor->isRemote()) {
            // Ignore notifications to remote statuses
            return Result::DROP;
        }

        if($target->id === $status->account_id) {
            $this->remoteAnnounceDeliver($status, $parent);
            return Result::ACK;
        }

        $parent->reblog_count = $parent->reblog_count + 1;
        $parent->save();

        Notification::firstOrCreate(
            [
                'target_account_id' => $target->id,
                'account_id' => $actor->id,
                'status_id' => $status->reblog_id ?? $status->id,
                'notify_type' => Notification::NOTIFY_TYPE_REBLOG,
            ]
        );

        return $this->remoteAnnounceDeliver($status, $parent);
    }

    public function remoteAnnounceDeliver(Status $status, Status $parent)
    {
        if (!$this->pushActivitypubSwitchOn($status->account, $status) || !$this->pushActivitypubSwitchOn($parent->account, $parent)) {
            return Result::DROP;
        }

        $account = $status->account;

        $audience = Follow::where('target_account_id', $account->id)->get()->filter(function($item) {
            return $item->account->isRemote();
        })->map(function($item) {
            return $item->account->inbox_uri;
        })->toArray();

        if(empty($audience) || $status->scope != Status::SCOPE_PUBLIC) {
            // Return on profiles with no remote followers
            return Result::DROP;
        }

        $apData = $this->getAnnounceApData($status, $parent);

        $client = $this->clientFactory->create([
            'headers' => [
                'User-Agent' => ActivitypubService::getUa(),
            ],
            'timeout' => 15
        ]);

        $parallel = new Parallel(5);
        Log::info('ReblogConsumer-audience&apData', compact('audience', 'apData'));
        foreach($audience as $url) {
            if (empty($url)) {
                continue;
            }
            $addHeaders = [
                'Content-Type'	=> 'application/activity+json; profile="'.ActivityPubActivityInterface::CONTEXT_URL.'"',
            ];
            $addHeaders = array_merge($addHeaders, Helper::zttpUserAgent());
            $headers = HttpSignature::sign($account, $url, $apData, $addHeaders);
            Log::info('ReblogConsumer to url:'.$url);
            $parallel->add(function() use ($client, $url, $headers, $apData) {
                return $client->post($url, [
                    'headers' => $headers,
                    'json' => $apData
                ]);
            });
        }

        try{
            $parallel->wait();
            Log::info('ReblogConsumer wait end');
        } catch(ParallelExecutionException $e) {
            Log::info('request exception', ['results' => $e->getMessage()]);
        }

        // send reply
        $r = $this->sendRelay($status, function ($status) use ($parent) {
            return $this->getAnnounceApData($status, $parent);
        }, __CLASS__);
        if ($r != Result::ACK) {
            return $r;
        }

        Log::info('ReblogConsumer final end');
        return Result::ACK;
    }
}
