<?php

declare(strict_types=1);

namespace App\Command\Status;

use App\Model\Status;
use Carbon\Carbon;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Command\Annotation\Command;
use Hyperf\DbConnection\Db;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Redis\Redis;
use Psr\Container\ContainerInterface;

#[Command]
class SyncStatusesViewCommand extends HyperfCommand
{
    #[Inject]
    protected Redis $redis;
    public function __construct(protected ContainerInterface $container)
    {
        parent::__construct('sync:status-view');
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('Hyperf Demo Command');
    }

    public function handle()
    {
        $keys = $this->redis->sMembers('status:views-keys');
        foreach ($keys as $key) {
            $this->info("handle status views {$key}");
            $time = Carbon::createFromFormat('Y-m-d H:i:s',$key);
            // 5 分钟内不处理
            if($time->diffInMinutes(Carbon::now())<5){
                $this->info("跳过处理当前时间");
                continue;
            }

            
            $statusViews = $this->redis->zRevRangeByScore(
                "status:views:{$key}",
                '+inf',
                '-inf',
                ['withscores' => true]
            );
            $this->handleStatusView($statusViews);
            $this->redis->sRem("status:views-keys",$key);
            $this->redis->del("status:views:{$key}");
            $this->info("end status views {$key}");
        }
    }
    public function handleStatusView($statusViews)
    {
        foreach ($statusViews as $id => $view) {
            $this->info("update status:{$id} views:{$view}");
            $view = (int)$view;
            Status::where('id', $id)->update([
                'view_count' => Db::raw("view_count + {$view}"),
                'view_count_updated_at' => Carbon::now()
            ]);
        }
    }
}
