<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */
namespace App\Controller;

use Hyperf\Cache\Annotation\Cacheable;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Psr\Container\ContainerInterface;
use Hyperf\Swagger\Annotation as OA;

use function Hyperf\Support\env;

#[OA\Info(title:"mastodon-php",version:'1.0.0')]
#[OA\Server(url:"https://activitypub.good.news")]
#[OA\SecurityScheme(name:'Authorization',type:'apiKey',in:'header')]
abstract class AbstractController
{
    #[Inject]
    protected ContainerInterface $container;

    #[Inject]
    protected RequestInterface $request;

    #[Inject]
    protected ResponseInterface $response;

    #[Cacheable(prefix: "client-ip", group: "co")]
    protected function getClientIp(): string
    {
        $realIp = $this->request->getHeader('x-real-ip');
        if($realIp){
            return is_array($realIp) ? $realIp[0] : $realIp;
        }
        return "0.0.0.0";
    }

    public function success($msg = '', $data = [])
    {
        return $this->response->json([
            'code' => 0,
            'msg'  => $msg,
            'data' => $data
        ]);
    }

    public function fail($msg, $code = -1)
    {
        return $this->response->json(compact('code', 'msg'));
    }
}
