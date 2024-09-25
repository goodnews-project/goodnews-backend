<?php

declare(strict_types=1);

namespace App\Command\Es;

use App\Model\Account;
use App\Model\Hashtag;
use App\Model\Status;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Command\Annotation\Command;
use Symfony\Component\Console\Input\InputArgument;

#[Command]
class SyncData extends HyperfCommand
{
    protected ?string $name = 'es:syncData';

    protected string $description = 'sync data';

    public function handle()
    {
        $action = $this->input->getArgument('action');
        $method = 'sync'.ucfirst($action);
        $this->{$method}();
    }

    public function syncAccount()
    {
        Account::all()->each(function (Account $item) {
            $item->created();
        });
    }

    public function syncStatus()
    {
        Status::all()->each(function (Status $item) {
            $item->created();
        });
    }

    public function syncHashtag()
    {
        Hashtag::all()->each(function (Hashtag $item) {
            $item->created();
        });
    }

    protected function getArguments()
    {
        return [
            ['action', InputArgument::REQUIRED, '同步动作']
        ];
    }
}
