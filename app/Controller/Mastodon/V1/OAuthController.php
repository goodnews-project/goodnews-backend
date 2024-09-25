<?php

declare(strict_types=1);

namespace App\Controller\Mastodon\V1;

use App\Controller\AbstractController;
use App\Request\UserRequest;
use App\Service\UserService;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpMessage\Cookie\Cookie;
use Hyperf\Swagger\Annotation as OA;
use function Hyperf\ViewEngine\view;

#[OA\HyperfServer('http')]
class OAuthController extends AbstractController
{
    #[Inject]
    protected UserService $userService;

    #[OA\Get(path:'/oauth/login',summary:'登录页',tags:['mastodon'])]
    public function login()
    {
        $params = $this->request->all();
//        if ($token = $this->request->cookie($this->getCookieName())) {
//            $params['token'] = $token;
//            return $this->response->redirect('/oauth/authorize?'.http_build_query($params));
//        }
        return view('passport.login', $params);
    }

    #[OA\Post(path:'/oauth/login',summary:'登录',tags:['mastodon'])]
    public function doLogin()
    {
        $params = $this->request->all();
        unset($params['email'], $params['password']);
        $data = $this->userService->login($this->request->input('email'), $this->request->input('password'));
        $params['token'] = $data['token'];
        $cookie = new Cookie($this->getCookieName(), $data['token']);
        return $this->response->withCookie($cookie)->redirect('/oauth/authorize?'.http_build_query($params));
    }

    #[OA\Get(path:'/oauth/reg',summary:'注册页',tags:['mastodon'])]
    public function reg()
    {
        return view('passport.reg', $this->request->all());
    }

    #[OA\Post(path:'/oauth/reg',summary:'注册',tags:['mastodon'])]
    public function doReg(UserRequest $userRequest)
    {
        $payload = $userRequest->validated();
        $this->userService->reg($payload);
        return $this->response->redirect('/oauth/login?'.http_build_query(['client_id' => $payload['client_id'], 'response_type' => $payload['response_type'], 'redirect_uri' => $payload['redirect_uri']]));
    }

    private function getCookieName($name = 'token'): string
    {
        return \Hyperf\Support\env('APP_ENV', 'local').'_'.$name;
    }
   
}
