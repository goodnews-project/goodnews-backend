<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\AbstractController;

use App\Model\Account;
use App\Model\User;
use App\Request\Admin\UserRequest;
use Carbon\Carbon;
use Hyperf\Swagger\Annotation as OA;

#[OA\HyperfServer('http')]
class UserController extends AbstractController
{
    #[OA\Get(path:'/admin/api/user/list',summary:'用户列表',tags:['admin', '运营用户'])]
    #[OA\Parameter(name: 'position', description: '位置 0:全部 1:本地 2:远程', in : 'query')]
    #[OA\Parameter(name: 'status', description: '管理状态 0:全部 1:已封禁', in : 'query')]
    #[OA\Parameter(name: 'role_id', description: '角色ID', in : 'query')]
    #[OA\Parameter(name: 'sortord', description: '排序方式 默认0 最近，1：最近活动', in : 'query')]
    #[OA\Parameter(name: 'username', description: '用户名', in : 'query')]
    #[OA\Parameter(name: 'domain', description: '域名', in : 'query')]
    #[OA\Parameter(name: 'nickname', description: '昵称', in : 'query')]
    #[OA\Parameter(name: 'email', description: '电子邮件地址', in : 'query')]
    #[OA\Parameter(name: 'ip', description: 'ip地址', in : 'query')]
    #[OA\Response(
        response: 200,
        description: ''
    )]
    public function list(UserRequest $userRequest)
    {
        $payload = $userRequest->validated();
        $q = $this->getQuery($payload);
        return $q->paginate(20);
    }

    #[OA\Put(path:'/admin/api/user/banned',summary:'封禁用户',tags:['admin', '用户'])]
    #[OA\Parameter(name: 'user_ids', description: '用户ID数组', in : 'query')]
    #[OA\Response(
        response: 200,
        description: ''
    )]
    public function banned(UserRequest $userRequest)
    {
        $payload = $userRequest->validated();
        if (!empty($payload['is_match_all'])) {
            $q = $this->getQuery($payload);
            $q->chunk(500, function ($results) {
                foreach ($results as $item) {
                    $account = Account::find($item->account_id);
                    if ($account) {
                        $account->suspended_at = Carbon::now();
                        $account->save();
                    }
                }
            });
            return $this->response->raw(null);
        }

        $userIds = $this->request->input('user_ids', []);
        User::findMany($userIds)->each(function(User $user) {
            $user->account->suspended_at = Carbon::now();
            $user->account->save();
        });
        return $this->response->raw(null);
    }

    private function getQuery(array $payload)
    {
        $position = $payload['position'] ?? 0;
        $status = $payload['status'] ?? 0;
        $role_id = $payload['role_id'] ?? 0;
        $sortord = $payload['sortord'] ?? 0;
        $username = $payload['username'] ?? '';
        $domain = $payload['domain'] ?? '';
        $nickname = $payload['nickname'] ?? '';
        $email = $payload['email'] ?? '';
        $ip = $payload['ip'] ?? '';
        $q = User::from('user as u')->rightJoin('account as a', 'u.account_id', '=', 'a.id');
        if ($position > 0) {
            $position == 1 ? $q->whereNull('a.domain') : $q->whereNotNull('a.domain');
        }

        if ($status > 0) {
            $status == 1 ? $q->whereNotNull('a.suspended_at') : $q->whereNull('a.suspended_at');
        }

        if ($role_id > 0) {
            $q->where('u.role_id',$role_id);
            // $q->leftJoin('user_role as ur', 'u.id', '=', 'ur.user_id')
                // ->where('ur.role_id', $role_id);

        }

        if ($username) {
            $q->where('a.acct', 'like', '%'.$username.'%');
        }

        if ($nickname) {
            $q->where('a.display_name', 'like', '%'.$nickname.'%');
        }

        if ($domain) {
            $q->where('a.domain', $domain);
        }

        if ($email) {
            $q->where('u.email', $email);
        }

        if ($ip) {
            $q->where('u.current_signin_ip', $ip);
        }

        $q->select('u.id as user_id', 'u.current_signin_at', 'a.username', 'a.domain', 'a.display_name', 'a.suspended_at', 'a.sensitized_at', 'a.id as account_id','a.avatar', 'a.acct', 'u.email', 'u.current_signin_ip', 'a.followers_count')
            ->selectRaw('(select count(1) from `status` where a.`id` = `status`.`account_id`) as status_count')
            ->selectRaw('(select count(1) from `status` where a.`id` = `status`.`account_id` and `status`.is_sensitive=1) as sensitive_status_count')
            ->selectRaw('if(a.suspended_at is null,0,1) as is_suspended');
        $sortord > 0 ? $q->latest('u.current_signin_at') : $q->latest('u.created_at');
        return $q;
    }
}
