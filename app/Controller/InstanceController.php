<?php

declare(strict_types=1);

namespace App\Controller;

use App\Aspect\Annotation\ExecTimeLogger;
use App\Model\Account;
use App\Model\Admin\InstanceRule;
use App\Model\Setting;
use App\Resource\InstanceResource;
use Carbon\Carbon;
use Hyperf\Cache\Annotation\Cacheable;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Redis\Redis;
use Hyperf\Swagger\Annotation as OA;

#[OA\HyperfServer('http')]

class InstanceController extends AbstractController
{
    #[Inject]
    protected Redis $redis;
    #[OA\Get(path:"/_api/v2/instance",summary:"实例信息",tags:['instance 实例'])]

    // #[Cacheable(prefix: "instance-index", ttl: 300)]
    #[ExecTimeLogger()]
    public function index()
    {
        $settings = Setting::whereNull('settingable_id')->whereIn('key',[
            'site_title',
            'site_short_description',
            'site_contact_username',
            'site_contact_email',
            'thumbnail_url'
        ])->pluck('value','key');
        if (empty($settings['site_contact_username'])) {
            return [];
        }
        $contactAccount  =  Account::where('acct', $settings['site_contact_username'])->firstOr(function (){
            return Account::oldest()->first();
        });

        $rules = InstanceRule::orderByDesc('id')->get(['text','id']);
        
        $endDate = Carbon::now()->subDays(10);
        $dates = [];
        for($date = Carbon::now()->copy(); $date->gte($endDate); $date->subDay()) {
            $dates[]= "active_users:".$date->toDateString();
        }
        $this->redis->bitOp('or','current_active_users',...$dates);
        $activeUserCount = $this->redis->bitCount('current_active_users');


        return (new InstanceResource(compact('settings','rules','contactAccount','activeUserCount')))
                ->toResponse();
    }
    

    #[OA\Get('/_api/v1/instance/extended_description',summary:"完整描述（关于）",tags:['instance 实例'])]
    public function extendedDescription()
    {
        return Setting::whereNull('settingable_id')->where('key','site_extended_description')
            ->first(['value as content','updated_at']); 
    }
}
