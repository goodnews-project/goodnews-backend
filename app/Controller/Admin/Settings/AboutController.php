<?php

declare(strict_types=1);

namespace App\Controller\Admin\Settings;

use App\Controller\AbstractController;
use App\Model\Setting;
use App\Request\Admin\Settings\AboutRequest;
use App\Service\SettingService;
use Hyperf\DbConnection\Db;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Redis\Redis;
use Hyperf\Swagger\Annotation as OA;

#[OA\HyperfServer('http')]
class AboutController extends AbstractController
{
    #[Inject]
    protected Redis $redis;

    #[OA\Get(path:'/_api/admin/settings/about',summary:'获取服务器关于',tags:['admin', '服务器设置'])]
    #[OA\Response(
        response: 200,
        description: ''
    )]
    public function index()
    {
        return Setting::whereNull('settingable_id')->whereIn('key',[
            'site_extended_description',
            'show_domain_blocks',
            'show_domain_blocks_rationale',
            'status_page_url',
            'site_terms',
        ])->pluck('value','key'); 
    }
    #[OA\Put(path:'/_api/admin/settings/about',summary:'设置服务器关于',tags:['admin', '服务器设置'])] 
    #[OA\Parameter(name: 'site_extended_description', description: '完整说明', in : 'body', required: true, )]
    #[OA\Parameter(name: 'show_domain_blocks', description: '显示域名屏蔽列表', in : 'body', required: true, )]
    #[OA\Parameter(name: 'show_domain_blocks_rationale', description: '显示域名屏蔽原因', in : 'body', required: true, )]
    #[OA\Parameter(name: 'status_page_url', description: '静态页面地址', in : 'body', required: true, )]
    #[OA\Parameter(name: 'site_terms', description: '本站缩略图', in : 'body', required: true, )]
    public function store(AboutRequest $request)
    {
        $payload = $request->validated();
        Db::transaction(function () use($payload){
            foreach($payload as $key => $value){
                Setting::updateOrCreate(['key' => $key],['value' => $value]);
            }
        });
        $this->redis->del(SettingService::S_SETTINGS_KEY);
        return $this->response->raw(null)->withStatus(204);
    }
}
