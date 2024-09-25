<?php

declare(strict_types=1);

namespace App\Command;

use App\Model\Account;
use App\Model\FollowRecommendation;
use App\Model\Status;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Command\Annotation\Command;
use Psr\Container\ContainerInterface;
use Hyperf\Crontab\Annotation\Crontab;
use Symfony\Component\Console\Output\ConsoleOutput;

#[Command]
#[Crontab(rule: "10 0 * * *", name: "follow-recommendation", callback: "executeCrontab", memo: "")]
class FollowRecommendationCommand extends HyperfCommand
{

    const FOLLOWERS_COUNT_RATE = 0.05;
    const STATUS_COUNT_RATE = 0.02;
    const FAVE_COUNT_RATE = 0.01;
    const REPLY_COUNT_RATE = 0.02;
    const REBLOG_COUNT_RATE = 0.01;

    public function __construct(protected ContainerInterface $container)
    {
        parent::__construct('follow:recommendation');
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('Generate a number of recommended follow accounts');
    }

    public function executeCrontab()
    {
        $this->output = new ConsoleOutput();
        $this->handle();
    }

    public function handle()
    {
        $accountRank = $this->getAccountRankByAccount();
        Status::whereIn('account_id', array_keys($accountRank))
            ->groupBy(['account_id'])
            ->selectRaw('account_id, sum(reply_count) as sum_reply_count, sum(fave_count) as sum_fave_count, sum(reblog_count) as sum_reblog_count')
            ->get()
            ->each(function ($item) use (&$accountRank) {
                $accountRank[$item->account_id] += (($item->sum_reply_count * self::REPLY_COUNT_RATE) + ($item->sum_fave_count * self::FAVE_COUNT_RATE) + ($item->sum_reblog_count * self::REBLOG_COUNT_RATE));
            });

        foreach ($accountRank as $accountId => $rank) {
            FollowRecommendation::updateOrCreate(['account_id' => $accountId], ['rank' => $rank]);
        }
    }

    public function getAccountRankByAccount()
    {
        $accountRank = [];
        Account::where('is_activate', 1)
            ->whereNotExists(function ($query) {
                $query->from('follow_recommendation as fr')
                    ->selectRaw('1')
                    ->whereColumn('account.id', '=', 'fr.account_id')
                    ->where('status', FollowRecommendation::STATUS_SUPPRESSED);
            })
            ->latest('followers_count')
            ->latest('status_count')
            ->limit(100)
            ->get()
            ->each(function (Account $account) use (&$accountRank) {
                FollowRecommendation::updateOrCreate(['account_id' => $account->id], ['language' => $account->language ?: 'zh-CN']);
                if (empty($accountRank[$account->id])) {
                    $accountRank[$account->id] = FollowRecommendation::where('account_id', $account->id)->value('rank') ?? 0;
                    return;
                }
                $accountRank[$account->id] += (($account->followers_count * self::FOLLOWERS_COUNT_RATE) + ($account->status_count * self::STATUS_COUNT_RATE));
            });
        return $accountRank;
    }
}
