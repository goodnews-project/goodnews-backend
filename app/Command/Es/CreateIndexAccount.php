<?php

declare(strict_types=1);

namespace App\Command\Es;

use App\Model\Account;
use App\Service\EsService;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Command\Annotation\Command;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Input\InputArgument;

#[Command]
class CreateIndexAccount extends HyperfCommand
{
    public function __construct(protected ContainerInterface $container)
    {
        parent::__construct('es:createIndex:account');
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('Create an index of account');
    }

    protected function getArguments()
    {
        return [
            ['action', InputArgument::REQUIRED, '动作']
        ];
    }

    public function handle()
    {
        $action = $this->input->getArgument('action');
        $es = Account::getEs();
        $body = [
            'settings' => [
                'number_of_shards' => 1,
                'number_of_replicas' => 0
            ],
            'mappings' => [
                'properties' => Account::ES_PROPERTIES
            ]
        ];

        if ($action == 'delete') {
            $resp = $es->deleteIndex();
        } elseif ($action == 'create') {
            $resp = $es->createIndex($body);
        } else {
            $this->info('Done Nothing!');
            return;
        }

        var_dump($resp);
    }
}
