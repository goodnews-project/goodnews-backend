<?php

namespace App\Service\Activitypub\Activity;

use App\Exception\InboxException;
use App\Model\Account;
use App\Model\Attachment;
use App\Model\Instance;
use App\Model\Status;
use App\Service\AttachmentServiceV2;
use App\Service\AttachmentServiceV3;
use App\Util\ActivityPub\Helper;

class Update extends Activity
{
    public function validate()
    {
        $activity = $this->object;

        if (!isset($activity['type'], $activity['id'])) {
            throw new InboxException('type and id is miss');
        }

        if (!Helper::validateUrl($activity['id'])) {
            throw new InboxException('id is not valid url');
        }
    }

    public function store()
    {
        $activity = $this->object;

        if ($activity['type'] === 'Note' && $status = Status::where('uri', $activity['id'])->first()) {
            return $this->remoteUpdateStatus($status, $activity);
        }

        if ($activity['type'] === 'Person') {
            $this->remoteUpdateAccount();
        }
    }

    public function remoteUpdateAccount()
    {
        $payload = $this->payload;

        if (empty($payload) || !isset($payload['actor'])) {
            return;
        }

        $account = Account::where('uri', $payload['actor'])->first();
        if (!$account) {
            throw new InboxException('remoteUpdateAccount:actor['.$payload['actor'].'] is null');
        }

        if ($account->isLocal()) {
            return;
        }

        if ($account->shared_inbox_uri == null || $account->shared_inbox_uri != $payload['object']['endpoints']['sharedInbox']) {
            $account->shared_inbox_uri = $payload['object']['endpoints']['sharedInbox'];
        }

        if ($account->public_key != $payload['object']['publicKey']['publicKeyPem']) {
            $account->public_key = $payload['object']['publicKey']['publicKeyPem'];
        }

        if ($account->note != $payload['object']['summary']) {
            $account->note = $payload['object']['summary'];
        }

        if ($account->display_name != $payload['object']['name']) {
            $account->display_name = $payload['object']['name'];
        }

        $account->profile_remote_image = $payload['object']['image']['url'] ?? '';
        if ($account->profile_remote_image == '') {
            $account->profile_image = '';
        } else {
            try {
                $account->profile_image = \Hyperf\Support\make(AttachmentServiceV3::class)->donwloadAndUpload($account->profile_remote_image);
            } catch (\Exception $e) {

            }
        }

        $account->avatar_remote_url = $payload['object']['icon']['url'] ?? '';
        if ($account->avatar_remote_url == '') {
            $account->avatar = '';
        } else {
            try {
                $account->avatar = \Hyperf\Support\make(AttachmentServiceV3::class)->donwloadAndUpload($account->avatar_remote_url);
            } catch (\Exception $e) {

            }
        }

        $account->save();

        if (!empty($payload['object']['extra']['wallet_address'])) {
            Helper::updateAccountData($account, ['wallet_address' => $payload['object']['extra']['wallet_address']]);
        }
    }

    public function remoteUpdateStatus(Status $status, $activity)
    {
        if (isset($activity['content'])) {
            $status->content = handleStatusContent($activity['content']);
        }
        if (isset($activity['sensitive'])) {
            $status->is_sensitive = (int) $activity['sensitive'];
        }
        $status->save();

        if (!isset($activity['attachment'])) {
            return null;
        }
        if ($status->attachments?->count() == 0) {
            return null;
        }

        $instance = Instance::where('domain', $status->account->domain)->first();

        $attachments = Attachment::where('tid', $status->id)
            ->where('from_table', Status::class)
            ->get();
        \Hyperf\Support\make(AttachmentServiceV2::class)->batchDeleteWithCloud($attachments);
        \Hyperf\Collection\collect($activity['attachment'])->each(function ($attachment) use ($status, $instance) {
            if (empty($attachment['url'])) {
                return;
            }

            $file_type = array_key_exists($attachment['mediaType'], AttachmentServiceV3::VIDEOS) ? Attachment::FILE_TYPE_VIDEO : Attachment::FILE_TYPE_IMAGE;
            $data = [
                'tid' => $status->id,
                'from_table' => Status::class,
                'file_type' => $file_type,
                'remote_url' => $attachment['url'],
                'name' => $attachment['name'] ?? null,
                'type' => $attachment['type'] ?? null,
                'media_type' => $attachment['mediaType'] ?? null,
                'blurhash' => $attachment['blurhash'] ?? null,
                'width' => $attachment['width'] ?? null,
                'height' => $attachment['height'] ?? null,
                'status' => Attachment::STATUS_WAIT,
            ];

            if (empty($instance) || !$instance->is_disable_download) {
                $data['status'] = Attachment::STATUS_FINISH;
                $m = Attachment::create($data);
                \Hyperf\Support\make(AttachmentServiceV3::class)->attachmentDownload($m->id);
            } else {
                Attachment::create($data);
            }
        });

        return null;
    }
}