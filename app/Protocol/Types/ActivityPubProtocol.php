<?php

namespace App\Protocol\Types;

use App\Entity\Contracts\ActivityPubActivityInterface;
use App\Model\Account;
use App\Nsq\Consumer\ActivityPub\Trait\ApRepository;
use App\Service\Activitypub\ActivitypubService;
use Hyperf\Di\Annotation\Inject;

class ActivityPubProtocol extends Protocol
{
    use ApRepository;

    #[Inject]
    protected ActivitypubService $activitypubService;

    public function follow($actorId, $targetActorId)
    {
        $account = Account::findOrFail($actorId);
        $target = Account::findOrFail($targetActorId);
        if (!$target->isRemote() || !$account->isLocal()) {
            return [];
        }

        return [
            '@context'  => ActivityPubActivityInterface::CONTEXT_URL,
            'id'        => $account->permalink('#follow/'.$target->id.'/undo'),
            'type'      => ActivityPubActivityInterface::TYPE_UNDO,
            'actor'     => $account->permalink(),
            'object'    => [
                'id' => $account->permalink('#follows/'.$target->id),
                'actor' => $account->permalink(),
                'object' => $target->permalink(),
                'type' => ActivityPubActivityInterface::TYPE_FOLLOW
            ]
        ];
    }

    public function unFollow($actorId, $targetActorId)
    {
        $account = Account::findOrFail($actorId);
        $target = Account::findOrFail($targetActorId);
        if (!$target->isRemote() || !$account->isLocal()) {
            return [];
        }

        return [
            '@context'  => ActivityPubActivityInterface::CONTEXT_URL,
            'id'        => $account->permalink('#follow/'.$target->id.'/undo'),
            'type'      => ActivityPubActivityInterface::TYPE_UNDO,
            'actor'     => $account->permalink(),
            'object'    => [
                'id' => $account->permalink('#follows/'.$target->id),
                'actor' => $account->permalink(),
                'object' => $target->permalink(),
                'type' => ActivityPubActivityInterface::TYPE_FOLLOW
            ]
        ];
    }

    public function post($actorId, array $content)
    {
        $status = $content['status'];
        $account = $content['account'];
        $to = $content['to'] ?? [];
        $cc = $content['cc'] ?? [];
        $inReplyToUri = $content['inReplyToUri'] ?? null;
        $inboxUrl = $content['inboxUrl'] ?? null;
        $tags = $content['tags'] ?? null;
        $data = [
            '@context' => [
                ActivityPubActivityInterface::SECURITY_URL,
                ActivityPubActivityInterface::CONTEXT_URL,
            ],
            'id'        => $status->permalink(),
            'type'      => ActivityPubActivityInterface::TYPE_CREATE,
            'actor'     => $account->permalink(),
            'published' => $status->published_at->toIso8601String(),
            'to'        => $to,
            'cc'        => $cc,
            'object'    => [
                'id'           => $status->permalink(),
                'type'         => ActivityPubActivityInterface::TYPE_NOTE,
                'summary'      => null,
                'content'      => $status->content,
                'contentMap'   => ['zh' => $status->content],
                'inReplyTo'    => $inReplyToUri,
                'atomUri'         => $status->permalink(),
                'inReplyToAtomUri'   => $inReplyToUri,
                'published'    => $status->published_at->toAtomString(),
                'url'          => $status->permaurl(),
                'attributedTo' => $account->permalink(),
                'to'           => $to,
                'cc'           => $cc,
                'sensitive'    => (bool) $status->is_sensitive,
                'attachment'   => $this->getAttachments($status, $this->getProxyUrlFunc($inboxUrl)),
                'tag'             => $tags,
                'commentsEnabled' => $status->comments_disabled == 0,
                'capabilities'    => [
                    'announce' => ActivityPubActivityInterface::PUBLIC_URL,
                    'like'     => ActivityPubActivityInterface::PUBLIC_URL,
                    'reply'    => $status->comments_disabled == 1 ? '[]' : ActivityPubActivityInterface::PUBLIC_URL
                ],
            ]
        ];

        if ($inReplyToUri) {
            $data['replies'] = [
                'id' => $status->permalink('replies'),
                'type' => ActivityPubActivityInterface::TYPE_COLLECTION,
                'first' => [
                    'type' => ActivityPubActivityInterface::TYPE_COLLECTION_PAGE,
                    'next' => $status->permalink('replies') . '?page=2',
                    'partOf' => $status->permalink('replies'),
                    'items' => []
                ]
            ];
        }
        return $data;
    }

    public function reBlog(string $actorId, array $content)
    {
        return [
            '@context'  => ActivityPubActivityInterface::CONTEXT_URL,
            'id'        => $actorId,
            'type'         => ActivityPubActivityInterface::TYPE_ANNOUNCE,
            'actor'        => $actorId,
            'to'         => [ActivityPubActivityInterface::PUBLIC_URL],
            'cc'         => $content['cc'],
            'published' => $content['published'],
            'object'    => $content['object'],
        ];
    }

    public function unReBlog(string $actorId, array $content)
    {
        return [
            '@context'  => ActivityPubActivityInterface::CONTEXT_URL,
            'id'		=> $actorId,
            'actor'		=> $content['actor'],
            'type'		=> ActivityPubActivityInterface::TYPE_UNDO,
            'object' 	=> [
                'id'		=> $content['object']['id'],
                'type' 		=> ActivityPubActivityInterface::TYPE_ANNOUNCE,
                'actor'		=> $content['actor'],
                'to' 		=> [ActivityPubActivityInterface::PUBLIC_URL],
                'cc'         => $content['cc'],
                'published' => $content['published'],
                'object'    => $content['object'],
            ]
        ];
    }

    public function delete(string $actorId, array $content)
    {
        return [
            '@context' => ActivityPubActivityInterface::CONTEXT_URL,
            'id'                     => $actorId,
            'type'                     => ActivityPubActivityInterface::TYPE_DELETE,
            'actor'                 => $content['actor'],
            'object'                 => [
                'id'                 => $content['id'],
                'type'                 => ActivityPubActivityInterface::TYPE_TOMBSTONE
            ]
        ];
    }

    public function like(string $actorId, array $content)
    {
        return [
            '@context'  => ActivityPubActivityInterface::CONTEXT_URL,
            'id'        => $actorId,
            'type'      => ActivityPubActivityInterface::TYPE_LIKE,
            'actor'     => $content['actor'],
            'object'    => $content['object']
        ];
    }

    public function unLike(string $actorId, array $content)
    {
        return [
            '@context'  => ActivityPubActivityInterface::CONTEXT_URL,
            'id'        => $actorId,
            'type'      => ActivityPubActivityInterface::TYPE_UNDO,
            'actor'     => $content['actor'],
            'object'    => [
                'id' => $content['id'],
                'actor' => $content['actor'],
                'object' => $content['object'],
                'type' => ActivityPubActivityInterface::TYPE_LIKE
            ]
        ];
    }

    public function handleInbox($requestData)
    {
        $this->activitypubService->inbox($requestData['headers'], $requestData['payload']);
    }
}