<?php

declare(strict_types=1);

namespace App\Command\Tool;

use App\Model\Scope\StatusWaitAttachmentScope;
use App\Model\Status;
use App\Service\Activitypub\DeleteRemoteAccount;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Command\Annotation\Command;
use Hyperf\DbConnection\Db;
use Psr\Container\ContainerInterface;

#[Command]
class FixDeletedAccountStatusCommand extends HyperfCommand
{


    public function __construct(protected ContainerInterface $container)
    {
        parent::__construct('tool:fix-deleted-account-status');
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('Delete status that deleted account');
    }

    public function handle()
    {
       $results = Db::select('select s.* from status s left join account a on s.`account_id`=a.id where a.id is null and s.deleted_at is null');

       $accountIds = \Hyperf\Collection\collect($results)->pluck('account_id')->unique();
       foreach ($accountIds as $accountId) {
           DeleteRemoteAccount::handle(null, $accountId, false);
           $this->info('deleting account['.$accountId.'] status');
       }
    }
}
