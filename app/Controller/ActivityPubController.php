<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */
namespace App\Controller;

use App\Entity\Contracts\ActivityPubActivityInterface;
use App\Exception\AppException;
use App\Middleware\ActivitypubMiddleware;
use App\Model\Account;
use App\Model\Attachment;
use App\Model\Follow;
use App\Model\Status;
use App\Nsq\Consumer\ActivityPub\Trait\ApRepository;
use App\Service\Activitypub\ActivitypubService;
use App\Service\Activitypub\ProcessInboxValidator;
use App\Service\UrisService;
use App\Util\Log;
use Carbon\Carbon;

use Hyperf\HttpServer\Annotation\Middleware;
use function Hyperf\Support\make;

class ActivityPubController extends AbstractController
{
    use ApRepository;

    public function user($username)
    {
       return ActivitypubService::user($username);
    }

    public function status(string $username, int $statusId)
    {
        $account = Account::where('username', $username)->whereNull('domain')->firstOrFail();
        $status = Status::findOrFail($statusId);
        if ($account->id != $status->account_id) {
            throw new AppException(sprintf('status with id %s does not belong to account with id %s', $statusId, $account->id));
        }

        if ($status->fee > 0) {
            throw new AppException('not found');
        }

        $proxyUrlFunc = function ($url, $remoteUrl) {
            return toProxyUrl($url, $remoteUrl);
        };

        $data = [
            '@context' => [
                ActivityPubActivityInterface::CONTEXT_URL,
                ActivityPubActivityInterface::SECURITY_URL,
            ],
            'attributedTo' => $account->permalink(),
            'cc'           => [$account->followers_uri ?: UrisService::generateURIsForAccount($username)['followersURI']],
            'to'           => [ActivityPubActivityInterface::PUBLIC_URL],
            'content'      => $status->content,
            'id'           => $status->permalink(),
            'atomUri'      => $status->permalink(),
            'inReplyTo'    => null,
            'attachment'   => $this->getAttachments($status, $proxyUrlFunc),
            'replies' => [
                'id'    => $status->permalink().'/replies',
                'type'  => ActivityPubActivityInterface::TYPE_COLLECTION,
                'first' => [
                    'id'     => $status->permalink().'/replies?page=1',
                    'partOf' => $status->permalink().'/replies',
                    'type'   => ActivityPubActivityInterface::TYPE_COLLECTION_PAGE,
                ]
            ],
            'type'      => ActivityPubActivityInterface::TYPE_NOTE,
            'sensitive' => $status->is_sensitive == 1,
            'url'       => $status->permaurl(),
            'published' => $status->published_at?->toIso8601String(),
            'tag'       => $this->getTags($status),
        ];

        if ($status->polls) {
            $data['type'] = ActivityPubActivityInterface::TYPE_QUESTION;
            $data['oneOf'] = $this->getOneOf($status);
        }

        return $data;
    }

    public function inbox(string $username)
    {
        $processInbox = make(ProcessInboxValidator::class,[
            'username' => $username,
            'request'  => $this->request
        ]);
        $processInbox->process();
        return $this->response->raw(null);
    }

    #[Middleware(ActivitypubMiddleware::class)]
    public function sharedInbox()
    {
        $processInbox = make(ProcessInboxValidator::class,[
            'username' => null,
            'request'  => $this->request
        ]);
        $processInbox->processShareInbox();
        return $this->response->raw(null);
    }

    public function outbox(string $username)
    {
        $page = $this->request->input('page', 0);
        $minId = max($this->request->input('min_id', 0), 0);
        $maxId = max($this->request->input('max_id', 0), 0);
        $account = Account::where('username', $username)->firstOrFail();

        if ($page <= 1) {
            return [
                '@context' => ActivityPubActivityInterface::CONTEXT_URL,
                'type'     => 'OrderedCollection',
                'id'       => $account->outbox_uri,
                'first'    => $account->outbox_uri.'?page=1',
                'last'     => $account->outbox_uri.'?page=1&min_id=0'
            ];
        }

        $q = Status::where('account_id', $account->id);
        if ($minId > 0) {
            $q->where('id', '>', $minId);
        }

        if ($maxId > 0) {
            $q->where('id', '<', $maxId);
        }

        $statusList = $q->where('scope', Status::SCOPE_PUBLIC)->latest('id')->paginate(30);

        $data = [
            'type'         => 'OrderedCollectionPage',
            'partOf'       => $account->outbox_uri,
            'orderedItems' => []
        ];

        $id = sprintf('%s?page=1', $account->outbox_uri);

        if ($minId > 0) {
            $id = sprintf('%s?page=1&min_id=%s', $account->outbox_uri, $minId);
        }

        if ($maxId > 0) {
            $id = sprintf('%s?page=1&max_id=%s', $account->outbox_uri, $maxId);
        }

        $data['id'] = $id;

        $highestId = 0;
        $lowestId = 0;
        foreach ($statusList as $status) {
            $orderedItem = [
                'id'        => $status->uri,
                'content'   => $status->content,
                'type'      => 'Create',
                'actor'     => $account->uri,
                'published' => Carbon::parse($status->published_at)->toIso8601String(),
                'to'        => [ActivityPubActivityInterface::PUBLIC_URL],
                'cc'        => [$account->uri.'/followers'],
                'object'    => $account->uri.'/statuses/'.$status->id
            ];

            $data['orderedItems'][] = $orderedItem;

            if ($status->id > $highestId) {
                $highestId = $status->id;
            }

            if ($status->id < $lowestId) {
                $lowestId = $status->id;
            }
        }

        if ($lowestId > 0) {
            $data['next'] = sprintf('%s?page=1&max_id=%s', $account->outbox_uri, $lowestId);
        }

        if ($highestId > 0) {
            $data['prev'] = sprintf('%s?page=1&min_id=%s', $account->outbox_uri, $highestId);
        }

        return $data;
    }

    public function followers(string $username)
    {
        $account = Account::where('username', $username)->firstOrFail();
        $follows = Follow::where('target_account_id', $account->id)->latest('updated_at')->get();

        $data = [
            '@context' => ActivityPubActivityInterface::CONTEXT_URL,
        ];
        foreach ($follows as $follow) {

            if (empty($follow->account)) {
                Log::warning(sprintf('follow %s missing account %s', $follow->id, $follow->account_id));
                continue;
            }
            $data['items'][] = $follow->account->permalink();
        }
        $data['type'] = ActivityPubActivityInterface::TYPE_ORDERED_COLLECTION;
        $data['totalItems'] = $follows->count();
        return $data;
    }

    public function following(string $username)
    {
        $account = Account::where('username', $username)->firstOrFail();
        $follows = Follow::where('account_id', $account->id)->latest('updated_at')->get();
        $data = [
            '@context' => ActivityPubActivityInterface::CONTEXT_URL,
            'type'     => ActivityPubActivityInterface::TYPE_ORDERED_COLLECTION
        ];
        foreach ($follows as $follow) {
            if (empty($follow->targetAccount)) {
                Log::warning(sprintf('follow %s missing target account %s', $follow->id, $follow->target_account_id));
                continue;
            }
            $data['items'][] = $follow->targetAccount->permalink();
        }
        $data['totalItems'] = $follows->count();
        return $data;
    }

    public function replies(string $username, int $statusId)
    {
        $page = max($this->request->input('page'), 1);
        $minId = max($this->request->input('min_id'), 0);
        $account = Account::where('username', $username)->firstOrFail();
        $status = Status::findOrFail($statusId);
        if ($status->account_id != $account->id) {
            throw new AppException(sprintf('status with id %s does not belong to account with id %s', $statusId, $account->id));
        }

        if ($status->fee > 0) {
            throw new AppException('not found');
        }

        if ($page <= 1) {
            return [
                '@context' => ActivityPubActivityInterface::CONTEXT_URL,
                'first'    => [
                    'id'   => $status->permalink().'/replies?page=1',
                    'type' => ActivityPubActivityInterface::TYPE_COLLECTION_PAGE
                ],
                'id'   => $status->permalink().'/replies',
                'type' => ActivityPubActivityInterface::TYPE_COLLECTION
            ];
        }

        $q = Status::where('reply_to_id', $statusId);
        if ($minId > 0) {
            $q->where('id', '>', $minId);
        }

        $data = [
            '@context' => ActivityPubActivityInterface::CONTEXT_URL,
            'id'       => $status->permalink().'/replies?page=1',
            'partOf'   => $status->permalink().'/replies',
            'items'    => [],
        ];
        if ($minId > 0) {
            $data['id'] = $status->permalink().'/replies?page=1&min_id='.$minId;
        }
        $replies = $q->where('scope', Status::SCOPE_PUBLIC)->get()->each(function ($item) use (&$data) {
            $data['items'][] = $item->uri;
        });

        $highestId = $replies->max('id');
        $data['next'] = $status->permalink().'/replies?page=1';
        if ($highestId > 0) {
            $data['next'] .= '&min_id='.$highestId;
        }

        return $data;
    }

    public function publicKey(string $username)
    {
        $account = Account::where('username', $username)->whereNull('domain')->firstOrFail();
        return [
            'id'                => $account->permalink(),
            'preferredUsername' => $account->username,
            'publicKey'         => [
                'id'           => $account->permalink('#main-key'),
                'owner'        => $account->permalink(),
                'publicKeyPem' => $account->public_key
            ]
        ];
    }
}
