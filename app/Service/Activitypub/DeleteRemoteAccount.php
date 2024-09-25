<?php

namespace App\Service\Activitypub;

use App\Model\Account;
use App\Model\Block;
use App\Model\Conversation;
use App\Model\DirectMessage;
use App\Model\Follow;
use App\Model\FollowRequest;
use App\Model\Mute;
use App\Model\Notification;
use App\Model\Poll;
use App\Model\PollVote;
use App\Model\Scope\StatusWaitAttachmentScope;
use App\Model\Status;
use App\Model\StatusesFave;
use App\Model\StatusesMention;
use Hyperf\DbConnection\Db;

class DeleteRemoteAccount
{
    public static function handle(Account|null $account, $accountId = null, $onlyRemote = true)
    {
        if ($accountId > 0) {
            $account = Account::find($accountId);
        }

        if ($account) {
            if($account->isLocal() && $onlyRemote) {
                return;
            }
            $accountId = $account->id;
        }

        // Delete statuses, todo 由于查询问题，使用原生查询代替
//        Status::withoutTrashed()->where('account_id', $accountId)
//            ->chunk(50, function($statuses) {
//                foreach($statuses as $status) {
//                    DeleteRemoteStatus::handle($status);
//                }
//            });

        $startId = 0;
        while (1) {
            $statusResults = Db::select('select status.* from `status` where `status`.id>? and `status`.`deleted_at` is null and `account_id` = ? order by id limit 50', [$startId, $accountId]);
            if (empty($statusResults)) {
                break;
            }
            $startId = $statusResults[count($statusResults) - 1]->id;
            foreach ($statusResults as $statusResult) {
                $status = Status::withoutGlobalScope(new StatusWaitAttachmentScope())->find($statusResult->id);
                if ($statusResult->id > 0 && $status) {
                    DeleteRemoteStatus::handle($status);
                }
            }
        }

        // Delete Poll Votes
        PollVote::where('account_id', $accountId)->delete();

        // Delete Polls
        Poll::where('account_id', $accountId)->delete();

        // Delete DMs
        DirectMessage::where('from_id', $accountId)->orWhere('to_id', $accountId)->delete();
        Conversation::where('from_id', $accountId)->orWhere('to_id', $accountId)->delete();

        // Delete FollowRequests
        FollowRequest::where('account_id', $accountId)
            ->orWhere('target_account_id', $accountId)
            ->delete();

        // Delete relationships
        Follow::where('account_id', $accountId)
            ->orWhere('target_account_id', $accountId)
            ->delete();

        // Delete likes
        StatusesFave::where('account_id', $accountId)->orWhere('target_account_id', $accountId)->delete();

        // Delete mutes/block
        Block::where('account_id', $accountId)->orWhere('target_account_id', $accountId)->delete();
        Mute::where('account_id', $accountId)->orWhere('target_account_id', $accountId)->delete();

        // Delete mentions
        StatusesMention::where('account_id', $accountId)->orWhere('target_account_id', $accountId)->delete();

        // Delete notifications
        Notification::where('account_id', $accountId)->orWhere('target_account_id', $accountId)->delete();

        // Delete accountt
        $account && $account->delete();
    }
}