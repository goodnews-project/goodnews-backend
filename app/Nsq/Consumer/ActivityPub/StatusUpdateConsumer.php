<?php

declare(strict_types=1);

namespace App\Nsq\Consumer\ActivityPub;

use App\Model\Account;
use App\Model\Status;
use App\Nsq\Consumer\ActivityPub\Trait\ApRepository;
use App\Nsq\Consumer\BaseConsumer;
use App\Nsq\Queue;
use Hyperf\Nsq\Annotation\Consumer;
use Hyperf\Nsq\Result;

#[Consumer(topic: Queue::TOPIC_STATUS_UPDATE, channel: Queue::CHANNEL_ACTVITYPUB, name: 'StatusConsumer', nums: 1)]
class StatusUpdateConsumer extends BaseConsumer
{
    use ApRepository;
    public function handle($data): ?string
    {
        $status = Status::findOrFail($data['id']);
        if ($status->scope == Status::SCOPE_DIRECT) {
            return Result::DROP;
        }

        $account = $status->account;
        $r = $this->send($status, $account, function ($status, $account, $to, $cc, $inReplyToUri, $tags, $inboxUrl) {
            return $this->getUpdateApData($status, $account, $to, $cc, $inReplyToUri, $tags, $inboxUrl);
        }, __CLASS__);

        if ($r != Result::ACK) {
            return $r;
        }
        return $this->sendRelay($status, function ($status, $account, $to, $cc, $inReplyToUri, $tags, $inboxUrl) {
            return $this->getUpdateApData($status, $account, $to, $cc, $inReplyToUri, $tags, $inboxUrl);
        }, __CLASS__);
    }
}
