<?php

namespace App\Controller\Admin\Account;

use App\Controller\AbstractController;
use App\Model\Account;
use App\Model\AccountWarning;
use App\Model\User;
use App\Service\UserService;
use Carbon\Carbon;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Redis\Redis;
use Hyperf\Swagger\Annotation as OA;
use Qbhy\HyperfAuth\AuthManager;

#[OA\HyperfServer('http')]
class ActionController extends AbstractController
{

    #[Inject]
    protected Redis $redis;

    #[Inject]
    protected AuthManager $auth;

    #[OA\Post('/_api/admin/accounts/{id}/disable', summary:"冻结账户", tags:["admin", "account"])]
    public function disable($id)
    {
        $targetAccount = Account::findOrFail($id);
        User::where('account_id',$id)->update(['is_disable' => 1]);
        AccountWarning::create([
            'target_account_id' => $targetAccount['id'],
            'action'            => AccountWarning::ACTION_DISABLE,
        ]);
        return $this->response->raw(null)->withStatus(201);
    }
    #[OA\Post('/_api/admin/accounts/{id}/enable', summary:"取消冻结账户", tags:["admin", "account"])]
    public function enable($id)
    {
        User::where('account_id',$id)->update(['is_disable' => 0]);
        return $this->response->raw(null)->withStatus(201);
    }

    #[OA\Post('/_api/admin/accounts/{id}/sensitive', summary:"敏感内容", tags:["admin", "account"])] 
    public function sensitive($id)
    {
        $targetAccount = Account::findOrFail($id); 
        $targetAccount->update([
            'sensitized_at' => Carbon::now()
        ]);
        AccountWarning::create([
            'target_account_id' => $targetAccount['id'],
            'action'            => AccountWarning::ACTION_SENSITIZED,
        ]);
        if ($token = $this->redis->get(sprintf(UserService::S_ACCOUNT_TOKEN_KEY, $targetAccount->id))) {
            $this->auth->logout($token);
        }
        return $this->response->raw(null)->withStatus(201); 
    }
    #[OA\Post('/_api/admin/accounts/{id}/un-sensitive', summary:"取消敏感内容", tags:["admin", "account"])]
    public function unSensitive($id)
    {
        $targetAccount = Account::findOrFail($id);
        $targetAccount->update([
            'sensitized_at' => null
        ]);
        if ($token = $this->redis->get(sprintf(UserService::S_ACCOUNT_TOKEN_KEY, $targetAccount->id))) {
            $this->auth->logout($token);
        }
        return $this->response->raw(null)->withStatus(201); 
    }
    
    #[OA\Post('/_api/admin/accounts/{id}/silence', summary:"隐藏账户", tags:["admin", "account"])] 
    public function silence($id)
    {
        $targetAccount = Account::findOrFail($id);
        $targetAccount->update([
            'silenced_at' => Carbon::now()
        ]); 
        AccountWarning::create([
            'target_account_id' => $targetAccount['id'],
            'action'            => AccountWarning::ACTION_SILENCED
        ]); 
        return $this->response->raw(null)->withStatus(201); 
    }
    #[OA\Post('/_api/admin/accounts/{id}/un-silence', summary:"取消隐藏账户", tags:["admin", "account"])]
    public function unSilence($id)
    {
        $targetAccount = Account::findOrFail($id);
        $targetAccount->update([
            'silenced_at' => null
        ]); 
        return $this->response->raw(null)->withStatus(201);  
    }
    #[OA\Post('/_api/admin/accounts/{id}/suspend', summary:"停用并永久删除账号数据", tags:["admin", "account"])]
    public function suspend($id)
    {
        $targetAccount = Account::findOrFail($id);
        $targetAccount->update([
            'suspended_at' => Carbon::now()
        ]); 
        AccountWarning::create([
            'target_account_id' => $targetAccount['id'],
            'action'            => AccountWarning::ACTION_SUSPENDED
        ]); 
        return $this->response->raw(null)->withStatus(201);  
    }
    #[OA\Post('/_api/admin/accounts/{id}/un-suspend', summary:"取消停用并永久删除账号数据", tags:["admin", "account"])]
    public function unSuspend($id)
    {
        $targetAccount = Account::findOrFail($id);
        $targetAccount->update([
            'suspended_at' => null
        ]); 
        return $this->response->raw(null)->withStatus(201);   
    }
}
