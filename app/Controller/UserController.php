<?php

declare(strict_types=1);

namespace App\Controller;

use App\Model\User;
use App\Request\UserRequest;
use App\Service\MailService;
use App\Service\NotificationService;
use App\Service\UserService;
use App\Util\Log;
use Exception;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Swagger\Annotation as OA;

#[OA\HyperfServer('http')]
class UserController extends AbstractController
{

    #[Inject]
    protected UserService $userService;
    #[Inject]
    protected NotificationService $notificationService;
    
    
    #[OA\Post(path:'/_api/v1/reg',summary:'用户注册',tags:['用户'])]
    #[OA\Parameter(name: 'username', description: '用户名,英文唯一标识', in : 'body', required: true)]
    #[OA\Parameter(name: 'display_name', description: '用户名,可以中文', in : 'body', required: true)]
    #[OA\Parameter(name: 'email', description: '邮箱', in : 'body', required: true)]
    #[OA\Parameter(name: 'password', description: '密码 6-18位', in : 'body', required: true)]
    #[OA\Parameter(name: 'locale', description: '客户端语言', in : 'body', required: true)]
    #[OA\Response(
        response: 201,
        description: '操作成功'
    )]
    public function reg(UserRequest $userRequest)
    {
       $payload = $userRequest->validated();
       $payload['clientIp'] = $this->getClientIp();
       try {
           $this->userService->reg($payload);
       } catch (\Exception $e) {
            return $this->response->json(['msg' => $e->getMessage()])->withStatus(403);
       }

       return $this->response->raw(null)->withStatus(201);
    }

    #[OA\Post(path:'/_api/v1/reg-send-email',summary:'用户注册邮件发送',tags:['用户'])]
    #[OA\Parameter(name: 'email', description: '邮箱', in : 'body', required: true)]
    #[OA\Response(
        response: 201,
        description: '操作成功'
    )]
    #[OA\Response(
        response: 403,
        description: '用户已激活'
    )]
    public function sendRegEmail()
    {
        $email = $this->request->input('email'); 
        $user = User::where('email',$email)->firstOrFail();
        if($user->confirmed_at){
            return $this->response->json(['msg' => '用户已激活'])->withStatus(403);
        }
        $confirmationToken = password_hash($email, PASSWORD_DEFAULT );
        $user->update([
            'confirmation_token' => $confirmationToken
        ]);
        MailService::sendReg($email,$confirmationToken); 
        return $this->response->raw(null)->withStatus(201); 
    }
    

    #[OA\Post(path:'/_api/v1/confirm',summary:'注册用户确认',tags:['用户'])]
    #[OA\Parameter(name: 'confirmation_token', description: '邮件内携带的token', in : 'body', required: true)]
    #[OA\Response(
        response: 403,
        description: '激活失败'
    )]
    #[OA\Response(
        response: 200,
        description: '激活成功 返回 token 和account 返回和登录成功相同'
    )]
    public function confirm()
    {
       $confirmationToken = $this->request->input('confirmation_token');
       $account = $this->request->input('account');

        if(!$confirmationToken){
            return $this->response->json(['msg' => '链接不正确，请重试'])->withStatus(401);
        }
        try {
            $data = $this->userService->confirm($account, $confirmationToken);
        } catch (\Exception $e) {
            return $this->response->json(['msg' => $e->getMessage()])->withStatus($e->getCode());
        }
        return $this->response->json($data);
    }

    #[OA\Get(path:'/_api/v1/is-confirm',summary:'检测用户是否验证邮箱',tags:['用户'])]
    #[OA\Parameter(name: 'email', description: '邮箱地址', in : 'body', required: true)]
    #[OA\Response(
        response: 403,
        description: '未验证'
    )]
    #[OA\Response(
        response: 200,
        description: '已验证，可以登录'
    )]
    public function isConfirm()
    {
        $email= $this->request->input('email'); 
        if (!$this->userService->isEmailConfirm($email)){
            return $this->response->json(['msg'=> '邮箱未验证'])->withStatus(403);
        }
        return $this->response->raw(null)->withStatus(204);
    }

    #[OA\Post(path:'/_api/v1/login',summary:'用户登录',tags:['用户'])]
    #[OA\Parameter(name: 'email', description: '用户邮箱', in : 'body', required: true)]
    #[OA\Parameter(name: 'password', description: '密码', in : 'body', required: true)]
    #[OA\Response(
        response: 403,
        description: '密码错误或未激活，直接返回 msg 即可'
    )]
    #[OA\Response(
        response: 200,
        description: 'account:用户信息 token:为token expire:为token过期时间单位s'
    )] 
    public function login()
    {
        $email = $this->request->input('email');
        $password = $this->request->input('password');

        try {
            $data = $this->userService->login($email, $password);
        } catch (\Exception $e) {
            return $this->response->json(['msg' => $e->getMessage()])->withStatus($e->getCode());
        }
        
        return $this->response->json($data);
    }

    #[OA\Post(path:'/_api/v1/refresh-token',summary:'刷新用户 token',tags:['用户'])]
    #[OA\Response(
        response: 200,
        description: 'token:为刷新后token expire:为token过期时间单位s'
    )] 
    public function refreshToken()
    {
        try{
            $token = $this->userService->refreshToken($this->request->getHeaderLine('Authorization'));
        }catch(Exception $e){
            Log::error("refresh failed",$e->getMessage());
            return $this->response->json([ 'msg' => 'token in blacklist'])->withStatus(401);
        }
        return $token;
    }

    #[OA\Post(path:'/_api/v1/logout',summary:'退出登录',tags:['用户'])]
    #[OA\Response(
        response: 200,
        description: '退出登录成功'
    )]  
    public function logout()
    {
       return $this->response->raw(null);
    }
}
