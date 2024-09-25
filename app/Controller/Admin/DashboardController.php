<?php

declare(strict_types=1);

namespace App\Controller\Admin;
use App\Model\Account;
use Hyperf\Swagger\Annotation as OA;
use App\Controller\AbstractController;
use App\Model\Status;
use App\Model\User;
use Carbon\Carbon;

#[OA\HyperfServer('http')]
class DashboardController extends AbstractController
{

    #[OA\Get('/_api/admin/dashboard')]
    #[OA\Response(
        response: 200,
        description: 'new_user 新用户 , active_user 活跃用户,status 互动, client 注册来源， lang 语言 domain 服务器,start_at 开始时间,end_at 结束时间'
    )]
    public function index()
    {
        $startAt = $this->request->input('start_at',Carbon::now()->subMonth()->toDateString());
        $endAt = $this->request->input('end_at',Carbon::now()->addDays(2)->toDateString());

        $user = [
            'total' => User::whereBetween('created_at',[$startAt,$endAt])->count(),
            'prev' => User::where('created_at','<',$startAt)->count()
        ];

        $activeUser = [
            'total'=> User::whereBetween('current_signin_at',[$startAt,$endAt])->count(),
            'prev'=> User::where('current_signin_at','<',$startAt)->count()
        ];
        $status = [
            'total'=>  Status::where('is_local',1)->whereBetween('published_at',[$startAt,$endAt])->count(),
            'prev' => Status::where('is_local',1)->where('published_at','<',$startAt)->count()
        ];

        $accountQuery = Account::query();
        if ($startAt) {
            $accountQuery->where('created_at', '>', $startAt);
        }
        if ($endAt) {
            $accountQuery->where('created_at', '<', $endAt);
        }
        $client = $accountQuery->whereNotNull('client')
            ->selectRaw('count(1) as total,client')
            ->groupBy(['client'])
            ->latest('total')
            ->get()
            ->each(function ($item) {
            $item->client_fmt = Account::clientMap[$item->client] ?? Account::clientMap[Account::CLIENT_WEB];
        });
        $lang = $accountQuery->whereNotNull('language')
            ->selectRaw('count(1) as total,language')
            ->groupBy(['language'])
            ->latest('total')
            ->get();
        $domain = $accountQuery->whereNotNull('domain')
            ->selectRaw('count(1) as total,domain')
            ->groupBy(['domain'])
            ->latest('total')
            ->limit(20)
            ->get();

        return $this->response->json([
            'new_user'=>$user,
            'active_user'=>$activeUser,
            'status'=> $status,
            'client'=> $client,
            'lang'=> $lang,
            'domain'=> $domain,
            'start_at'=> $startAt,
            'end_at'=> $endAt
        ]);
    }
}
