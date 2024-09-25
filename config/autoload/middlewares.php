<?php

declare(strict_types=1);

use App\Middleware\ActivitypubMiddleware;

/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */
return [
    'http' => [
        \App\Middleware\CorsMiddleware::class,
        \Hyperf\Validation\Middleware\ValidationMiddleware::class,
        \Hyperf\Session\Middleware\SessionMiddleware::class,
    ],
];
