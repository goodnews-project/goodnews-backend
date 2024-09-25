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

use App\Exception\HttpResponseException;
use Hyperf\Logger\LoggerFactory;
use Hyperf\ExceptionHandler\ExceptionHandler;
use Hyperf\ExceptionHandler\Formatter\FormatterInterface;
use Hyperf\HttpMessage\Exception\HttpException;
use Hyperf\HttpMessage\Stream\SwooleStream;
use Hyperf\HttpServer\Contract\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Throwable;

class HttpResponseExceptionHandler extends ExceptionHandler
{
    public function __construct(
        protected LoggerFactory $logger,
        protected FormatterInterface $formatter,
        protected RequestInterface $request,
    ) {
    }

    /**
     * Handle the exception, and return the specified result.
     * @param HttpException $throwable
     */
    public function handle(Throwable $throwable, ResponseInterface $response)
    {
        $this->stopPropagation();
        /** @var HttpResponseException $throwable  */
        return $response->withAddedHeader('content-type', 'application/json; charset=utf-8')
            ->withBody(new SwooleStream(json_encode([
                'message' => $throwable->getMessage(),
                'errors' =>  $throwable->errors
            ])))->withStatus($throwable->getCode());
    }

    /**
     * Determine if the current exception handler should handle the exception.
     *
     * @return bool If return true, then this exception handler will handle the exception,
     *              If return false, then delegate to next handler
     */
    public function isValid(Throwable $throwable): bool
    {
        return $throwable instanceof HttpResponseException;
    }
}
