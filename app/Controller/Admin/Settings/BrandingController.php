<?php

declare(strict_types=1);

namespace App\Controller\Admin\Settings;

use App\Controller\AbstractController;
use App\Model\Attachment;
use App\Model\Setting;
use App\Request\Admin\Settings\BrandingRequest;
use App\Service\SettingService;
use Hyperf\DbConnection\Db;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Redis\Redis;
use Hyperf\Swagger\Annotation as OA;

#[OA\HyperfServer('http')]
class BrandingController extends AbstractController
{
    #[Inject]
    protected Redis $redis;

    #[OA\Get(path:'/_api/admin/settings/branding',summary:'获取服务器招牌',tags:['admin', '服务器设置'])]
    #[OA\Response(
        response: 200,
        description: ''
    )]
    public function index()
    {
        return Setting::whereNull('settingable_id')->whereIn('key',[
            'site_title',
            'site_contact_username',
            'site_contact_email',
            'site_short_description',
            'thumbnail_id',
            'thumbnail_url',
            'receive_remote_sensitive',
            'push_local_sensitive'
        ])->pluck('value','key');
    }

    #[OA\Put(path:'/_api/admin/settings/branding',summary:'设置服务器招牌',tags:['admin', '服务器设置'])] 
    #[OA\Parameter(name: 'site_title', description: '本站名称', in : 'body', required: true, )]
    #[OA\Parameter(name: 'site_contact_username', description: '本站联系人', in : 'body', required: true, )]
    #[OA\Parameter(name: 'site_contact_email', description: '本站联系邮箱', in : 'body', required: true, )]
    #[OA\Parameter(name: 'site_short_description', description: '本站简介', in : 'body', required: true, )]
    #[OA\Parameter(name: 'thumbnail_id', description: '本站缩略图', in : 'body', required: true, )]
    #[OA\Parameter(name: 'receive_remote_sensitive', description: '接收站外敏感内容广播, 1 接收 0 否', in : 'body', required: true, )]
    #[OA\Parameter(name: 'push_local_sensitive', description: '对外广播本站敏感内容 1 是 0 否', in : 'body', required: true, )]
    public function store(BrandingRequest $request)
    {
        $payload = $request->validated();
        if(!empty($payload['thumbnail_id'])){
            $attachment = Attachment::where('id', $payload['thumbnail_id'])->first();
            $payload['thumbnail_url'] = $attachment->url;
            $attachment->update(['tid' => 0, 'from_table' => Setting::class]);
        }
        
        Db::transaction(function () use($payload){
            foreach($payload as $key => $value){
                Setting::updateOrCreate(['key' => $key],['value' => $value]);
            }
        });
        $this->redis->del(SettingService::S_SETTINGS_KEY);

        return $this->response->raw(null)->withStatus(201); 
    }

}
