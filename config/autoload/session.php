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
use Hyperf\Session\Handler;

$sessionDir = BASE_PATH . '/runtime/session';
!is_dir($sessionDir) AND mkdir($sessionDir, 0755, true);
return [
    'handler' => Handler\RedisHandler::class,
    'options' => [
        'connection' => 'default',
        'path' => $sessionDir,
        'gc_maxlifetime' => 1200,
        'session_name' => 'HYPERF_SESSION_ID',
        'domain' => null,
        'cookie_lifetime' => 5 * 60 * 60,
        'cookie_same_site' => 'lax',
        'secure' => true,
    ],
];
