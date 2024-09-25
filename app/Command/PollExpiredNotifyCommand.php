<?php

declare(strict_types=1);

namespace App\Command;

use App\Model\Notification;
use App\Model\Poll;
use App\Model\PollVote;
use Carbon\Carbon;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Command\Annotation\Command;
use Hyperf\Crontab\Annotation\Crontab;
use Psr\Container\ContainerInterface;

#[Command]
#[Crontab(name: "poll-expired-notify", rule: "* * * * *", callback: "executeCrontab", memo: "")]
class PollExpiredNotifyCommand extends HyperfCommand
{
    public function __construct(protected ContainerInterface $container)
    {
        parent::__construct('notify:poll-expired');
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('poll-expired notify');
    }

    public function handle()
    {
        Poll::where('expires_at', '<', Carbon::now())
            ->where('expires_at', '>', Carbon::now()->subMinutes(5))
            ->get()
            ->each(function (Poll $poll) {
                $poll->votes->each(function (PollVote $pollVote) use ($poll) {
                    $this->notify($poll->account_id, $pollVote->account_id, $poll->status_id);
                });
                $this->notify($poll->account_id, $poll->account_id, $poll->status_id);
            });

    }

    public function notify($accountId, $targetAccountId, $statusId)
    {
        $pollNotify = Notification::where('account_id', $accountId)
            ->where('target_account_id', $targetAccountId)
            ->where('notify_type', Notification::NOTIFY_TYPE_POLL)
            ->where('status_id', $statusId)
            ->where('read', 0)
            ->first();
        if ($pollNotify) {
            return;
        }
        Notification::create([
            'account_id' => $accountId,
            'target_account_id' => $targetAccountId,
            'notify_type' => Notification::NOTIFY_TYPE_POLL,
            'status_id' => $statusId,
        ]);
    }

    public function executeCrontab()
    {
        $this->handle();
    }
}
