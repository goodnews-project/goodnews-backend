<?php

declare(strict_types=1);

namespace App\Controller;

use Hyperf\Swagger\Annotation as OA;
use Hyperf\HttpServer\Annotation\Middleware;
use App\Middleware\AuthMiddleware;
use App\Model\User;
use App\Request\ChangePasswordRequest;
use App\Service\Auth;

use function Hyperf\Translation\trans;

#[OA\HyperfServer('http')]
#[Middleware(AuthMiddleware::class)]
class ChangePasswordController extends AbstractController
{
    #[OA\Post(path: '/_api/v1/change-password', summary: '修改密码', tags: ['修改密码'])]
    #[OA\Parameter(name: 'old_password', description: '旧密码', in: 'body', required: true)]
    #[OA\Parameter(name: 'password', description: '新密码', in: 'body', required: true)]
    #[OA\Response(
        response: 200,
        description: '操作成功'
    )]
    #[OA\Response(
        response: 403,
        description: '旧密码错误'
    )]
    public function change(ChangePasswordRequest $changePasswordRequest)
    {
        $payload = $changePasswordRequest->validated();
        $account = Auth::account();

        $user = User::where('account_id', $account['id'])->firstOrFail();
        if (!password_verify($payload['old_password'], $user->encrypted_password)) {
            return $this->response->json(['msg' => trans('message.change_password_error.old_password_error')])->withStatus(403);
        }
        $user->update([
            'reset_password_token'   => null,
            'reset_password_sent_at' => null,
            'encrypted_password'     => password_hash($payload['password'], PASSWORD_DEFAULT)
        ]);
        return $this->response->raw(null)->withStatus(204);
    }
}
