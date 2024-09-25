<?php

declare(strict_types=1);

use App\Exception\Handler\JWTExceptionHandler;

/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */
return [
    'handler' => [
        'http' => [
            // Hyperf\HttpServer\Exception\Handler\HttpExceptionHandler::class,
            \App\Exception\Handler\HttpExceptionHandler::class,
            \App\Exception\Handler\ValidationExceptionHandler::class,
            \App\Exception\Handler\ModelNotFoundExceptionHandler::class,
            \App\Exception\Handler\InboxExceptionHandler::class,
            \App\Exception\Handler\HttpResponseExceptionHandler::class,
            \Qbhy\HyperfAuth\AuthExceptionHandler::class,
            JWTExceptionHandler::class,
            App\Exception\Handler\AppExceptionHandler::class,
            \Richard\HyperfPassport\PassportExceptionHandler::class,

            \App\Exception\Handler\KernelExceptionHandler::class
        ],
    ],
];
