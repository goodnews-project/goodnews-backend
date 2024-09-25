<?php

declare(strict_types=1);

namespace App\Nsq\Consumer\ActivityPub;

use App\Entity\Contracts\ActivityPubActivityInterface;
use App\Model\Attachment;
use App\Model\DirectMessage;
use App\Nsq\Consumer\BaseConsumer;
use App\Nsq\Queue;
use App\Util\ActivityPub\Helper;
use Hyperf\Nsq\Annotation\Consumer;
use Hyperf\Nsq\Result;

#[Consumer(topic: Queue::TOPIC_SEND_MESSAGE, channel: Queue::CHANNEL_ACTVITYPUB, name: 'SendMessageConsumer', nums: 1)]
class SendMessageConsumer extends BaseConsumer
{
    public function handle($data): ?string
    {
        if (empty($data['dmId'])) {
            return Result::DROP;
        }
        $this->remoteDeliver(DirectMessage::findOrFail($data['dmId']));
        return Result::ACK;
    }

    public function remoteDeliver(DirectMessage $dm)
    {
        $account = $dm->author;
        $url = $dm->recipient->inbox_uri;

        $tags = [
            [
                'type' => 'Mention',
                'href' => $dm->recipient->permalink(),
                'name' => $dm->recipient->permaurl(),
            ]
        ];

        $body = [
            '@context' => [
                ActivityPubActivityInterface::SECURITY_URL,
                ActivityPubActivityInterface::CONTEXT_URL,
            ],
            'id'                    => $dm->status->permalink(),
            'type'                  => 'Create',
            'actor'                 => $dm->status->account->permalink(),
            'published'             => $dm->status->created_at->toAtomString(),
            'to'                    => [$dm->recipient->permalink()],
            'cc'                    => [],
            'object' => [
                'id'                => $dm->status->permalink(),
                'type'              => 'Note',
                'summary'           => null,
                'content'           => $dm->status->content,
                'inReplyTo'         => null,
                'published'         => $dm->status->created_at->toAtomString(),
                'url'               => $dm->status->permaurl(),
                'attributedTo'      => $dm->status->account->permalink(),
                'to'                => [$dm->recipient->permalink()],
                'cc'                => [],
                'sensitive'         => (bool) $dm->status->is_sensitive,
                'attachment'        => $dm->status->attachments()->get()->map(function (Attachment $media) {
                    return [
                        'type'      => $media->type,
                        'mediaType' => $media->media_type,
                        'url'       => $media->url,
                        'name'      => $media->name,
                    ];
                })->toArray(),
                'tag'               => $tags,
            ]
        ];

        Helper::sendSignedObject($account, $url, $body);
    }
}
