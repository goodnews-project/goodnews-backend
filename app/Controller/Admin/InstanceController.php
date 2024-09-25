<?php

namespace App\Controller\Admin;
use App\Controller\AbstractController;
use App\Model\Account;
use App\Model\Instance;
use App\Request\Admin\InstanceSettingRequest;
use App\Service\MetricsService;
use Carbon\Carbon;
use Hyperf\DbConnection\Db;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Swagger\Annotation as OA;
#[OA\HyperfServer('http')]
class InstanceController extends AbstractController
{
    #[Inject]
    protected MetricsService $metricsService;

    #[OA\Get('/_api/admin/instances',summary:"后台实例列表",tags:["后台-实例"])]
    public function index()
    {
        return Account::whereNotNull('domain')
            ->groupBy('domain')
            ->orderBy(Db::raw('SUM(status_count)'),'desc')
            ->select(Db::raw('SUM(status_count) as status_count'),'domain')
            ->paginate(20);
    }

    #[OA\Get('/_api/admin/instances/settings',summary:"后台实例配置列表",tags:["后台-实例"])]
    public function settings()
    {
        return Instance::paginate(20);
    }

    #[OA\Put('/_api/admin/instances/{domain}/setting',summary:"后台实例配置保存",tags:["后台-实例"])]
    #[OA\Parameter(name: 'is_disable_download', description: '是否下载远程资源: 1 禁用', in: 'body', required: true)]
    #[OA\Parameter(name: 'is_proxy', description: '是否代理远程资源： 1 代理', in : 'body', required: true)]
    #[OA\Parameter(name: 'is_disable_sync', description: '是否关闭同步：1 关闭同步', in : 'body', required: true)]
    public function settingsSave($domain,InstanceSettingRequest $request)
    {
        $payload = $request->validated();
        Instance::where('domain',$domain)->updateOrCreate([
            'domain'=> $domain
        ],$payload);
        return $this->response->raw(null)->withStatus(201);
    }

    #[OA\Get('/_api/admin/instances/{domain}',summary:"后台实例详情",tags:["后台-实例"])]
    #[OA\Response(
        response: 200,
        description: 'account_count 储存账号 status_count 储存的嘟文 attachment_sum 存储的媒体文件单位KB
         follow_count 对方关注者 follower_count 本站关注者
         report_count 关于对方的举报 availability 可用性
         follower_rank 被关注最多的账号
        '
    )]
    public function show($domain)
    {
        return [
            'account_count'  => $this->metricsService->domainAccountCount($domain),
            'status_count'   => $this->metricsService->domainStatusCount($domain),
            'attachment_sum' => $this->metricsService->domainAttachmentSum($domain),
            'follow_count'   => $this->metricsService->domainFollowCount($domain),
            'follower_count' => $this->metricsService->domainFollowerCount($domain),
            'report_count'   => $this->metricsService->domainReportCount($domain),
            'follower_rank'  => $this->metricsService->domainFollowerRank($domain),
            'availability'   => $this->metricsService->domainAvailability($domain,Carbon::now()->subDays(15),Carbon::now()),
            'instance'       => $this->metricsService->getInstanceByDomain($domain),
        ];       
    }

   
}
