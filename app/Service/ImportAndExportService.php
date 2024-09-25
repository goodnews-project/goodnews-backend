<?php

namespace App\Service;

use App\Entity\Contracts\ActivityPubActivityInterface;
use App\Model\Attachment;
use App\Model\Block;
use App\Model\Bookmark;
use App\Model\Export;
use App\Model\Follow;
use App\Model\Import;
use App\Model\Mute;
use App\Model\Status;
use App\Model\StatusesFave;
use App\Nsq\Consumer\ActivityPub\Trait\ApRepository;
use App\Nsq\NsqQueueMessage;
use App\Service\Activitypub\ActivitypubService;
use App\Util\ActivityPub\Helper;
use Carbon\Carbon;
use GuzzleHttp\RequestOptions;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Guzzle\ClientFactory;
use League\Flysystem\Filesystem;
use function Hyperf\Support\env;

class ImportAndExportService
{
    use ApRepository;
    #[Inject]
    protected AccountService $accountService;

    #[Inject]
    protected StatusesService $statusesService;

    #[Inject]
    protected ActivitypubService $activitypubService;

    #[Inject]
    private ClientFactory $clientFactory;

    #[Inject]
    protected Filesystem $filesystem;

    const ACTION_MAP = [
        Import::TYPE_FOLLOWING => 'importFollowing',
        Import::TYPE_MUTE => 'importMuted',
        Import::TYPE_BLOCK => 'importBlocked',
        Import::TYPE_BOOKMARK => 'importBookmarks',
    ];

    const DOWNLOAD_MAP = [
        'follows.csv' => 'downloadFollows',
        'mutes.csv' => 'downloadMutes',
        'blocks.csv' => 'downloadBlocks',
        'bookmarks.csv' => 'downloadBookmarks',
    ];

    const IMPORT_MAP = [
        'following_accounts.csv' => Import::TYPE_FOLLOWING,
        'muted_accounts.csv' => Import::TYPE_MUTE,
        'blocked_accounts.csv' => Import::TYPE_BLOCK,
        'bookmarks.csv' => Import::TYPE_BOOKMARK,
    ];

    const MODE_MERGE = 1;
    const MODE_OVERRIDE = 2;

    public function downloadFollows($authAccountId)
    {
        $datas = [];
        Follow::where('account_id', $authAccountId)
            ->get()
            ->each(function (Follow $follow) use (&$datas) {
                $datas[] = [$follow->targetAccount?->acct, 'TRUE', 'FALSE', ''];
            });

        $header = ['Account address', 'Show boosts', 'Notify on new posts', 'Languages'];
        return [$header, $datas];
    }

    public function downloadMutes($authAccountId)
    {
        $datas = [];
        Mute::where('account_id', $authAccountId)
            ->get()
            ->each(function (Mute $mute) use (&$datas) {
                $datas[] = [$mute->targetAccount?->acct, 'TRUE'];
            });

        $header = ['Account address', 'Hide notifications'];
        return [$header, $datas];
    }

    public function downloadBlocks($authAccountId)
    {
        $datas = [];
        $header = null;
        Block::where('account_id', $authAccountId)
            ->get()
            ->each(function (Block $block) use (&$datas) {
                $datas[] = [$block->targetAccount?->acct];
            });

        return [$header, $datas];
    }

    public function downloadBookmarks($authAccountId)
    {
        $datas = [];
        $header = null;
        Bookmark::where('account_id', $authAccountId)
            ->get()
            ->each(function (Bookmark $bookmark) use (&$datas) {
                if (!$bookmark->status) {
                    return;
                }
                $datas[] = [$bookmark->status->permalink()];
            });

        return [$header, $datas];
    }

    public function importFollowing($authAccountId, $datas, $mode)
    {
        if ($mode == self::MODE_OVERRIDE) {
            Follow::where('account_id', $authAccountId)
                ->get()
                ->each(function (Follow $follow) {
                $follow->delete();
            });
        }

        foreach ($datas as $data) {
            $acct = $data[0];
            $account = WebfingerService::lookup($acct);
            if (empty($account)) {
                continue;
            }
            $this->accountService->follow($account['id'], $authAccountId);
        }
    }

    public function importMuted($authAccountId, $datas, $mode)
    {
        if ($mode == self::MODE_OVERRIDE) {
            Mute::where('account_id', $authAccountId)
                ->get()
                ->each(function (Mute $mute) {
                    $mute->delete();
                });
        }

        foreach ($datas as $data) {
            $acct = $data[0];
            $account = WebfingerService::lookup($acct);
            if (empty($account)) {
                continue;
            }
            $this->accountService->mute($authAccountId, $account['id']);
        }
    }

    public function importBlocked($authAccountId, $datas, $mode)
    {
        if ($mode == self::MODE_OVERRIDE) {
            Block::where('account_id', $authAccountId)
                ->get()
                ->each(function (Block $block) {
                    $block->delete();
                });
        }

        foreach ($datas as $data) {
            $acct = $data[0];
            $account = WebfingerService::lookup($acct);
            if (empty($account)) {
                continue;
            }
            $this->accountService->block($authAccountId, $account['id']);
        }
    }

    public function importBookmarks($authAccountId, $datas, $mode)
    {
        if ($mode == self::MODE_OVERRIDE) {
            Bookmark::where('account_id', $authAccountId)
                ->get()
                ->each(function (Bookmark $bookmark) {
                    $bookmark->delete();
                });
        }

        foreach ($datas as $data) {
            $uri = $data[0];
            if (Helper::validateLocalUrl($uri)) {
                $status = Status::find(ltrim(strrchr($uri, '/'), '/'));
            } else {
                $status = Helper::statusFirstOrFetch($uri);
            }

            if (empty($status)) {
                continue;
            }
            $this->statusesService->bookmark($authAccountId, $status->id);
        }
    }

    public function exportRequest($accountId)
    {
        if (!$this->showRequestBtn($accountId)) {
            return;
        }

        $export = Export::create(['account_id' => $accountId, 'status' => Export::STATUS_EXPORTING]);
        $this->generateApFile($export->id);
    }

    #[NsqQueueMessage]
    public function generateApFile($exportId)
    {
        $export = Export::findOrFail($exportId);
        $account = $export->account;
        $accountId = $account->id;
        $d = BASE_PATH.'/storage/archive-'.date('YmdHis').'-'.uniqid();
        $mediaAttachmentsD = $d.'/media_attachments';
        !is_dir($mediaAttachmentsD) && mkdir($mediaAttachmentsD, 0755, true);
        !is_dir($d) && mkdir($d, 0755, true);

        // avatar.png
        $client = $this->clientFactory->create();
        $client->get($account->avatar, [
            RequestOptions::SINK => $d.'/avatar.png'
        ]);

        // actor.json
        $actor = $this->activitypubService->user($account->username);
        $actor['likes'] = 'likes.json';
        $actor['bookmarks'] = 'bookmarks.json';
        $actorJsonSize = file_put_contents($d.'/actor.json', json_encode($actor));
        var_dump('write actor.json '.$actorJsonSize);

        // bookmarks.json
        $statusIds = Bookmark::where('account_id', $accountId)->pluck('status_id');
        $statusUris = Status::whereIn('id', $statusIds)->pluck('uri');
        $bookmarks = [
            '@context' => ActivityPubActivityInterface::CONTEXT_URL,
            'id' => 'bookmarks.json',
            'type' => ActivityPubActivityInterface::TYPE_ORDERED_COLLECTION,
            'totalItems' => $statusUris->count(),
            'orderedItems' => $statusUris->toArray(),
        ];
        $bookmarksJsonSize = file_put_contents($d.'/bookmarks.json', json_encode($bookmarks));
        var_dump('write bookmarks.json '.$bookmarksJsonSize);

        // likes.json
        $statusIds = StatusesFave::where('account_id', $accountId)->pluck('status_id');
        $statusUris = Status::whereIn('id', $statusIds)->pluck('uri');
        $likes = [
            '@context' => ActivityPubActivityInterface::CONTEXT_URL,
            'id' => 'likes.json',
            'type' => ActivityPubActivityInterface::TYPE_ORDERED_COLLECTION,
            'totalItems' => $statusUris->count(),
            'orderedItems' => $statusUris->toArray(),
        ];
        $likesJsonSize = file_put_contents($d.'/likes.json', json_encode($likes));
        var_dump('write likes.json '.$likesJsonSize);

        // outbox.json
        $orderedItems = [];
        $statusList = Status::where('account_id', $accountId)
            ->whereIn('scope', [Status::SCOPE_PUBLIC, Status::SCOPE_UNLISTED, Status::SCOPE_PRIVATE])
            ->get()
            ->each(function (Status $status) use (&$orderedItems, $account, $client, $mediaAttachmentsD) {
                $orderedItems[] = [
                    'id' => $status->permalink(),
                    'type' => ActivityPubActivityInterface::TYPE_CREATE,
                    'actor' => $account->permalink(),
                    'published' => $status->published_at,
                    'to' => [ActivityPubActivityInterface::PUBLIC_URL],
                    'cc' => [UrisService::generateURIsForAccount($account->username)['followersURI']],
                    'object' => [
                        'id' => $status->permalink(),
                        'type' => ActivityPubActivityInterface::TYPE_NOTE,
                        'summary' => $status->spoiler_text,
                        'inReplyTo' => null,
                        'published' => $status->published_at,
                        'url' => $status->permaurl(),
                        'attributedTo' => $account->permalink(),
                        'to' => [ActivityPubActivityInterface::PUBLIC_URL],
                        'cc' => [UrisService::generateURIsForAccount($account->username)['followersURI']],
                        'sensitive' => (bool) $status->is_sensitive,
                        'content' => $status->content_rendered,
                        'attachment' => $status->attachments->transform(function (Attachment $attachment) use ($client, $account, $mediaAttachmentsD, $status) {
                            $attachmentFilename = $attachment->id.strrchr($attachment->url, '.');
                            $attachmentDir = $mediaAttachmentsD.'/'.$status->id;
                            !is_dir($attachmentDir) && mkdir($attachmentDir, 0755, true);
                            $attachmentRelativePath = '/media_attachments/'.$status->id.'/'.$attachmentFilename;
                            $attachmentPath = $attachmentDir.'/'.$attachmentFilename;
                            $client->get($attachment->url, [
                                RequestOptions::SINK => $attachmentPath
                            ]);
                            return [
                                'type' => $attachment->type,
                                'mediaType' => $attachment->media_type,
                                'url' => $attachmentRelativePath,
                                'name' => $attachment->name,
                                'blurhash' => $attachment->blurhash,
                                'width' => $attachment->width,
                                'height' => $attachment->height,
                            ];
                        }),
                        'tag' => $this->getTags($status)
                    ]
                ];
        });
        $outbox = [
            '@context' => ActivityPubActivityInterface::CONTEXT_URL,
            'id' => 'outbox.json',
            'type' => ActivityPubActivityInterface::TYPE_ORDERED_COLLECTION,
            'totalItems' => $statusList->count(),
            'orderedItems' => $orderedItems,
        ];
        $outboxJsonSize = file_put_contents($d.'/outbox.json', json_encode($outbox));
        var_dump('write outbox.json '.$outboxJsonSize);

        $tarFilename = ltrim(strrchr($d, '/'), '/').'.tar.gz';
        $tarfile = BASE_PATH.'/storage/'.$tarFilename;
        $pd = new \PharData($tarfile, format: \Phar::GZ);
        $pd->buildFromDirectory($d);
        $tarfileSize = filesize($tarfile);
        $location = '/remote/exports/'.date('Y-m-d').'/'.$tarFilename;
        $this->filesystem->write($location, file_get_contents($tarfile));

        is_file($tarfile) && unlink($tarfile);
        is_dir($d) && $this->deldir($d);

        $export->status = Export::STATUS_EXPORTED;
        $export->file_url = env('ATTACHMENT_PREFIX').$location;
        $export->filesize = $tarfileSize;
        $export->save();

        return null;
    }

    public function deldir($dir) {
        if (!is_dir($dir)) {
            return false;
        }

        $files = array_diff(scandir($dir), array('.', '..'));

        foreach ($files as $file) {
            $filePath = $dir . '/' . $file;

            if (is_dir($filePath)) {
                $this->deldir($filePath);
            } else {
                unlink($filePath);
            }
        }

        return rmdir($dir);
    }

    public function guessedType($header, $filename)
    {
        $type = null;
        $header = (array) $header;
        if (in_array('Hide notifications', $header) || str_starts_with($filename, 'mutes') || str_starts_with($filename, 'muted_accounts')) {
            $type = Import::TYPE_MUTE;
        } elseif (in_array('Show boosts', $header) || in_array('Notify on new posts', $header) || in_array('Languages', $header)) {
            $type = Import::TYPE_FOLLOWING;
        } elseif (str_starts_with($filename, 'blocks') || str_starts_with($filename, 'blocked_accounts')) {
            $type = Import::TYPE_BLOCK;
        } elseif (str_starts_with($filename, 'bookmarks')) {
            $type = Import::TYPE_BOOKMARK;
        }
        return $type;
    }

    public function showRequestBtn($accountId)
    {
        $exports = Export::where('account_id', $accountId)->get();
        if ($exports->isEmpty()) {
            return true;
        }

        $exporting = $exports->where('status', Export::STATUS_EXPORTING)->first();
        if ($exporting) {
            return false;
        }
        $latestExport = $exports->sortByDesc('id')->first();
        return $latestExport->created_at->lt(Carbon::now()->subDays(7));
    }
}
