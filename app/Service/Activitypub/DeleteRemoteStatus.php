<?php

namespace App\Service\Activitypub;

use App\Model\Attachment;
use App\Model\Bookmark;
use App\Model\DirectMessage;
use App\Model\Notification;
use App\Model\Status;
use App\Model\StatusesFave;
use App\Model\StatusesMention;
use App\Model\StatusHashtag;
use App\Service\Websocket;

class DeleteRemoteStatus
{
    public static function handle(Status $status)
    {
        self::unlinkRemoveMedia($status);
    }

    public static function unlinkRemoveMedia(Status $status)
    {

        if($status->reply_to_id) {
            $parent = Status::find($status->reply_to_id);
            if($parent) {
                $parent->where('reply_count', '>', 0)->decrement('reply_count');
            }
        }

        Bookmark::where('status_id', $status->id)->delete();
        DirectMessage::where('status_id', $status->id)->delete();
        StatusesFave::where('status_id', $status->id)->delete();
        Attachment::where('tid', $status->id)->where('from_table', Status::class)->delete();
        StatusesMention::where('status_id', $status->id)->delete();
        Notification::where('status_id', $status->id)->delete();
        StatusHashtag::where('status_id', $status->id)->delete();;
        Status::where('reply_to_id', $status->id)->update(['reply_to_id' => null]);

        $status->delete();

        Websocket::pushNormalizePayload(Websocket::STREAM_PUBLIC_HOME, Websocket::EVENT_DELETE, (string) $status->id);
        Websocket::pushNormalizePayload(Websocket::STREAM_PUBLIC_REMOTE, Websocket::EVENT_DELETE, (string) $status->id);
        Websocket::pushNormalizePayload(Websocket::STREAM_PUBLIC_LOCAL, Websocket::EVENT_DELETE, (string) $status->id);
    }
}