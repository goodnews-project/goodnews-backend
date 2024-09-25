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

use App\Exception\InboxException;
use Hyperf\Contract\ContainerInterface;
use Hyperf\ExceptionHandler\ExceptionHandler;
use Hyperf\HttpMessage\Stream\SwooleStream;
use Hyperf\Logger\LoggerFactory;
use Hyperf\Validation\ValidationException;
use Psr\Http\Message\ResponseInterface;
use Throwable;

class InboxExceptionHandler extends ExceptionHandler
{
    public function __construct(protected LoggerFactory $logger)
    {

    }
    public function handle(Throwable $throwable, ResponseInterface $response)
    {
        $this->logger->get('inbox','inbox')->error(sprintf('%s[%s] in %s', $throwable->getMessage(), $throwable->getLine(), $throwable->getFile()));
        $this->logger->get('inbox','inbox')->error($throwable->getTraceAsString());
        return $response;
    }

    public function isValid(Throwable $throwable): bool
    {
        return $throwable instanceof InboxException;
    }

}
