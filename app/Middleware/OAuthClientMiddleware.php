<?php

declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Richard\HyperfPassport\PassportAuthMiddleware;
use Richard\HyperfPassport\Client;

class OAuthClientMiddleware extends PassportAuthMiddleware
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
        foreach ($this->guards as $name) {
            $guard = $this->auth->guard($name);

            if (!$guard->client() instanceof Client) {
                throw new \Richard\HyperfPassport\Exception\PassportException("Without authorization from {$guard->getName()} guard", $guard);
            }
        }

        return $handler->handle($request);
    }
}
