<?php

declare(strict_types=1);

namespace App\Middleware;

use Hyperf\HttpMessage\Cookie\Cookie;
use Hyperf\HttpServer\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Qbhy\HyperfAuth\Exception\UnauthorizedException;

class OAuthOrRedirectToLoginMiddleware extends AuthMiddleware
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return parent::process($request, $handler);
        } catch (UnauthorizedException $e) {
            return (new Response())->redirect('/oauth/login?'.http_build_query($request->getQueryParams()));
        }

    }
}
