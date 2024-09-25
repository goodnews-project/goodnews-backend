<?php

namespace App\Resource\Mastodon;

use App\Model\Status;
use App\Resource\Mastodon\Model\AccountModel;
use App\Resource\Mastodon\Model\ApplicationModel;
use App\Resource\Mastodon\Model\StatusModel;
use Carbon\Carbon;
use Hyperf\Resource\Json\JsonResource;

class StatusResource extends JsonResource
{
    public ?string $wrap = null;
    /**
     * Transform the resource into an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        if (!$this->resource instanceof Status) {
            return [];
        }
        $status = $this->resource;

        $application = [
            'name'      => 'Web',
            'website'   => null
        ];
        if ($status->application_id > 0) {
            $application = ApplicationResource::make($status->application);
        }
        return [
            'id' => (string)$status->id,
            'uri' => (string)$status->permalink(),
            'created_at' => $status->published_at?->toIso8601ZuluString('m') ?: Carbon::now()->toIso8601ZuluString('m'),
            'account' => AccountResource::make($status->account),
            'content' => (string) $status->content_rendered,
            'visibility' => (string) $status->visibility,
            'sensitive' => (bool) $status->is_sensitive,
            'spoiler_text' => (string) $status->spoiler_text,
            'media_attachments' => AttachmentCollection::make($status->attachments),
            'application' => $application,
            'mentions' => MentionCollection::make($status->mentions),
            'tags' => TagCollection::make($status->hashtags),
            'reblogs_count' => (int)$status->reblog_count,
            'favourites_count' => (int)$status->fave_count,
            'replies_count' => (int)$status->reply_count,
            'url' => $status->permaurl(),
            'in_reply_to_id' => $status->reply_to_id ? (string) $status->reply_to_id : null,
            'in_reply_to_account_id' => $status->reply_to_account_id ? (string) $status->reply_to_account_id : null,
            'reblog' => $status->originStatus ? self::make($status->originStatus) : null,
            'poll' => $status->polls ? PollResource::make($status->polls) : null,
            'card' => $status->previewCard ? PreviewCardResource::make($status->previewCard) : null,
            'language' => 'en',
            'text'=>  null,
            'edited_at'=> $status->edited_at?->toIso8601ZuluString('m'),
            'favourited' => !is_null($status->statusesFave),
            'reblogged' => !is_null($status->reblog),
            'muted' => !is_null($status->mute),
            'bookmarked' => !is_null($status->bookmarked),
            'pinned'=> !is_null($status->pinned_at),
            'filtered'=> $status->filter ? FilterResultCollection::make([$status->filter]) : [],
            'emojis' =>  EmojiCollection::make($status->emoji),
        ];
    }

    public function getModel()
    {
        if (!$this->resource instanceof Status) {
            return null;
        }
        $status = $this->resource;

        $resModel = new StatusModel();
        $resModel->id = (string) $status->id;
        $resModel->created_at = $status->published_at ?: Carbon::now()->toIso8601String();
        $resModel->in_reply_to_id = (string) $status->reply_to_id;
        $resModel->in_reply_to_account_id = (string) $status->reply_to_account_id;
        $resModel->sensitive = (bool) $status->is_sensitive;
        $resModel->spoiler_text = '';
        $resModel->visibility = $status->visibility;

        $appModel = new ApplicationModel();
        $appModel->id = '';
        $appModel->client_id = '';
        $appModel->client_secret = '';
        $appModel->name = 'web';
        $appModel->redirect_uri = '';
        $appModel->vapid_key = '';
        $appModel->website = '';
        $resModel->application = $appModel;
        $resModel->language = 'en';
        $resModel->uri = $status->permalink();
        $resModel->url = $status->permaurl();
        $resModel->replies_count = $status->reply_count;
        $resModel->reblogs_count = $status->reblog_count;
        $resModel->favourites_count = $status->fave_count;
        $resModel->favourited = !is_null($status->statusesFave);
        $resModel->reblogged = !is_null($status->reblog);
        $resModel->muted = !is_null($status->mute);
        $resModel->bookmarked = !is_null($status->bookmarked);
        $resModel->content = (string) $status->content;
        $resModel->reblog = $status->reblog ? self::make($status->reblog)->getModel() : null;

        $resModel->account = AccountResource::make($status->account)->getModel();
        $resModel->media_attachments = AttachmentCollection::make($status->attachments)->toArray();
        $resModel->mentions = MentionCollection::make($status->mentions)->toArray();
        $resModel->tags = TagCollection::make($status->hashtags)->toArray();
        $resModel->emojis =  EmojiCollection::make($status->emoji)->toArray();
        $resModel->poll = $status->polls ? PollResource::make($status->polls)->getModel() : null;
        $resModel->card = $status->previewCard ? PreviewCardResource::make($status->previewCard)->getModel() : null;
        $resModel->pinned = is_null($status->pinned_at);
        $resModel->text = '';
        return $resModel;
    }
}
