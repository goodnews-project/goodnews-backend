<?php

declare(strict_types=1);

namespace App\Middleware;

use Hyperf\Di\Annotation\Inject;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Qbhy\HyperfAuth\AuthManager;
use Qbhy\HyperfAuth\Authenticatable;
use Qbhy\HyperfAuth\Exception\UnauthorizedException;

class AuthMiddleware implements MiddlewareInterface
{
    #[Inject]
    protected AuthManager $auth;

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $guard = $this->auth->guard("jwt");
        if (! $guard->user() instanceof Authenticatable) {
            throw new UnauthorizedException("Without authorization from {$guard->getName()} guard", $guard);
        }


        return $handler->handle($request);
    }

}