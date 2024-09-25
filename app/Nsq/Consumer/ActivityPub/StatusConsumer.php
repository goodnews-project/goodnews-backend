<?php

declare(strict_types=1);

namespace App\Nsq\Consumer\ActivityPub;

use App\Model\Account;
use App\Model\Poll;
use App\Model\Status;
use App\Nsq\Consumer\ActivityPub\Trait\ApRepository;
use App\Nsq\Consumer\BaseConsumer;
use App\Nsq\Queue;
use App\Resource\Mastodon\StatusResource;
use App\Service\Websocket;
use Hyperf\Nsq\Annotation\Consumer;
use Hyperf\Nsq\Result;

#[Consumer(topic: Queue::TOPIC_STATUS_CREATE, channel: Queue::CHANNEL_ACTVITYPUB, name: 'StatusConsumer', nums: 1)]
class StatusConsumer extends BaseConsumer
{
    use ApRepository;
    public function handle($data): ?string
    {
        $status = Status::findOrFail($data['id']);
        if ($status->scope == Status::SCOPE_DIRECT) {
            return Result::DROP;
        }

        // 付费内容填充
        $this->statusPaidContentPad($status);

        $payload = StatusResource::make($status);
        Websocket::pushPublicLocal($payload);
        Websocket::pushPublicRemote($payload);

        $account = $status->account;
        $r = $this->send($status, $account, function ($status, $account, $to, $cc, $inReplyToUri, $tags, $inboxUrl) {
            return $this->getCreateOrPollApData($status, $account, $to, $cc, $inReplyToUri, $tags, $inboxUrl);
        }, __CLASS__);

        if ($r != Result::ACK) {
            return $r;
        }

        return $this->sendRelay($status, function ($status, $account, $to, $cc, $inReplyToUri, $tags, $inboxUrl) {
            return $this->getCreateOrPollApData($status, $account, $to, $cc, $inReplyToUri, $tags, $inboxUrl);
        }, __CLASS__);
    }
}
