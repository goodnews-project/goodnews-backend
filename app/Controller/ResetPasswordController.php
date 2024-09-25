<?php

declare(strict_types=1);

namespace App\Controller;

use App\Exception\HttpResponseException;
use App\Model\User;
use App\Request\ResetPasswordRequest;
use App\Service\MailService;
use Carbon\Carbon;
use Hyperf\HttpMessage\Exception\HttpException;
use Hyperf\Stringable\Str;
use Hyperf\Swagger\Annotation as OA;

#[OA\HyperfServer('http')]
class ResetPasswordController extends AbstractController
{
    #[OA\Post(path: '/_api/v1/reset-password/send-mail', summary: '忘了密码发送邮件', tags: ['忘了密码'])]
    #[OA\Parameter(name: 'email', description: '邮箱', in: 'body', required: true)]
    #[OA\Response(
        response: 200,
        description: '操作成功'
    )]
    public function sendEmail(ResetPasswordRequest $request)
    {
        $payload = $request->validated();
        $user = User::where('email', $payload['email'])->firstOrFail();
        $resetPasswordToken = Str::random(32);
        $user->update([
            'reset_password_token'   => $resetPasswordToken,
            'reset_password_sent_at' => Carbon::now()
        ]);
        MailService::sendResetPassword($payload['email'], $resetPasswordToken);
        return $this->response->json(['msg' => '重置成功，请检查您的邮箱']);
    }

    #[OA\Post(path: '/_api/v1/reset-password/reset', summary: '重置密码', tags: ['忘了密码'])]
    #[OA\Parameter(name: 'token', description: 'token', in: 'body', required: true)]
    #[OA\Parameter(name: 'password', description: '密码', in: 'body', required: true)]
    #[OA\Response(
        response: 200,
        description: '操作成功'
    )]
    #[OA\Response(
        response: 403,
        description: '验证码错误'
    )]
    public function reset(ResetPasswordRequest $request)
    {
        $payload = $request->validated();
        $user = User::where('reset_password_token', $payload['token'])->first();
        if (!$user) {
            return $this->response->json([
                'msg'=> '验证失败'
            ])->withStatus(403);
        }
        if ($user->reset_password_sent_at->diffInMinutes(Carbon::now()) > 25) {
            return $this->response->json([
                'msg'=> '链接仅在25分钟内有效'
            ])->withStatus(403);
        }
       
        $user->update([
            'reset_password_token'   => null,
            'reset_password_sent_at' => null,
            'encrypted_password'     => password_hash($payload['password'], PASSWORD_DEFAULT)
        ]);

        return $this->response->json(['msg' => '密码修改完成']);
    }

    protected function validateToken($token)
    {
        $user = User::where('reset_password_token', $token)->first();
        if ($user->reset_password_sent_at->diffInMinutes(Carbon::now()) > 25) {
            throw new HttpResponseException( '链接仅在25分钟内有效',403);
        }
        if (!$user) {
            throw new HttpResponseException('验证失败',403);
        }
        return $user;
    }
}
