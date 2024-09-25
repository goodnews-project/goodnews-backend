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
namespace App\Exception\Handler;

use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\ExceptionHandler\ExceptionHandler;
use Hyperf\ExceptionHandler\Formatter\FormatterInterface;
use Hyperf\HttpMessage\Exception\HttpException;
use Hyperf\HttpMessage\Stream\SwooleStream;
use Hyperf\HttpServer\Contract\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Throwable;

class HttpExceptionHandler extends ExceptionHandler
{
    public function __construct(
        protected StdoutLoggerInterface $logger, 
        protected FormatterInterface $formatter,
        protected RequestInterface $request,
    )
    {
    }

    /**
     * Handle the exception, and return the specified result.
     * @param HttpException $throwable
     */
    public function handle(Throwable $throwable, ResponseInterface $response)
    {
        // $staticFile = __DIR__ . $request->server['request_uri'];
        // if (! file_exists($staticFile)) {
        //     return false;
        // }
        // $type = pathinfo($staticFile, PATHINFO_EXTENSION);
        // if (! isset($static[$type])) {
        //     return false;
        // }
        // $response->header('Content-Type', $static[$type]);
        // $response->sendfile($staticFile);


        $this->logger->error("404:" . (string)$this->request->getUri());
        $this->stopPropagation();
        return $response->withStatus($throwable->getStatusCode())->withBody(new SwooleStream($throwable->getMessage()));
    }

    /**
     * Determine if the current exception handler should handle the exception.
     *
     * @return bool If return true, then this exception handler will handle the exception,
     *              If return false, then delegate to next handler
     */
    public function isValid(Throwable $throwable): bool
    {
        return $throwable instanceof HttpException;
    }
}
