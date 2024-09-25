<?php

declare(strict_types=1);

namespace App\Controller;

use App\Middleware\AuthMiddleware;
use App\Model\Account;
//use App\Model\WalletAddressLog;
use App\Request\WalletRequest;
use App\Service\Auth;
use App\Service\Web3Service;
use Hyperf\DbConnection\Db;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Hyperf\Swagger\Annotation as OA;

#[OA\HyperfServer('http')]
#[Middleware(AuthMiddleware::class)]
class WalletController extends AbstractController
{

    #[OA\Get('/_api/v1/wallet/message')]
    public function message()
    {
        $message = [
            'time' => time(),
            'account_id' => Auth::account()['id']
        ];

        ksort($message);
        $messageStr = http_build_query($message);
        $message['signature'] = strtoupper(hash_hmac("sha256", $messageStr, "good.news"));
        return $this->response->json([
            'message' => base64_encode(json_encode($message))
        ]);
    }
    #[OA\Post('/_api/v1/wallet/verify-signature')]
    #[OA\Parameter(name: 'message', description: '上一步的message', in: 'body', required: true)]
    #[OA\Parameter(name: 'signature', description: '签名', in: 'body', required: true)]
    #[OA\Parameter(name: 'wallet_address', description: '用户钱包地址', in: 'body', required: true)]
    #[OA\Response(
        response: 204,
        description: '绑定成功',
    )]
    public function verifySignature(WalletRequest $request)
    {
        $payload =  $request->validated();
        $account = Auth::account();
        if (!$this->web3Service->verifySignature(
            $payload['message'],
            $payload['signature'],
            $payload['wallet_address']
        )) {
            return $this->response->json(['msg' => '绑定失败,请重试'])->withStatus(403);
        }

        $payload['wallet_address'] = strtolower($payload['wallet_address']);

        Account::findOrFail($account['id'])->update([
            'wallet_address' => $payload['wallet_address']
        ]);
        return $this->response->raw(null)->withStatus(204);
    }
}
