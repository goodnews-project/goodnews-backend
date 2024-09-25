<?php

declare(strict_types=1);

namespace App\Middleware;

use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\Logger\LoggerFactory;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

class ActivitypubMiddleware implements MiddlewareInterface
{
    protected LoggerInterface $logger;
    public function __construct(
        protected ContainerInterface $container,
        protected RequestInterface $request,
    ) {
        $this->logger = $container->get(LoggerFactory::class)->get('log', 'activitypub_request');
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {

        $response = $handler->handle($request);

        $msg = null;
        if ($body = json_decode($response->getBody()->getContents(), true) != null) {
            if (!empty($body['error'])) {
                $msg = $body['error'];
            }
        }

        $this->logger->info("activitypub request", [
            "url" => $this->request->getRequestUri(),
            "body" => $this->request->getBody()->__toString(),
            'query' => $this->request->getQueryString(),
            'response_code' => $response->getStatusCode(),
            'response_msg' => $msg
        ]);
        return $response->withHeader('Content-Type', 'application/activity+json;charset=utf-8');
    }
}
