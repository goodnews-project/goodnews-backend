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

use App\Exception\AppException;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\ExceptionHandler\ExceptionHandler;
use Hyperf\HttpMessage\Stream\SwooleStream;
use Psr\Http\Message\ResponseInterface;
use Throwable;

class AppExceptionHandler extends ExceptionHandler
{
    public function __construct(protected StdoutLoggerInterface $logger) {}

    public function handle(Throwable $throwable, ResponseInterface $response)
    {
        $this->stopPropagation();
        /** @var AppException $throwable  */
        return $response->withAddedHeader('content-type', 'application/json; charset=utf-8')
            ->withBody(new SwooleStream(json_encode([
                'msg' => $throwable->tranMessage(),
                'code'=>$throwable->appCode
            ])))->withStatus($throwable->httpCode);
    }

    public function isValid(Throwable $throwable): bool
    {
        
        return $throwable instanceof AppException;
    }
}
