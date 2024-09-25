<?php

declare(strict_types=1);

namespace App\Command;

use App\Model\Account;
use App\Model\Status;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Command\Annotation\Command;
use Psr\Container\ContainerInterface;

#[Command]
class FixStatusCountCommand extends HyperfCommand
{
    public function __construct(protected ContainerInterface $container)
    {
        parent::__construct('fix:status-count');
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('Hyperf Demo Command');
    }

    public function handle()
    {
        Account::whereNull('uri')->chunkById(100,function ($accounts ){
            foreach($accounts as $account){
                $this->info("start account {$account['acct']}");
                $statusCount = Status::where('account_id',$account['id'])->count();
                if($statusCount!=$account['status_count']){
                    $this->error("{$account['acct']}:{$account['status_count']}fix to {$statusCount}");
                    $account->update([
                        'status_count'=> $statusCount
                    ]);
                    return ;
                }
            }
        });
    }
}
