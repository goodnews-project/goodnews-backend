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

use Hyperf\Server\Event;
use Hyperf\Server\Server;
use Swoole\Constant;

return [
    'mode'    => SWOOLE_PROCESS,
    'servers' => [
        [
            'name'      => 'http',
            'type'      => Server::SERVER_HTTP,
            'host'      => '0.0.0.0',
            'port'      => intval(\Hyperf\Support\env('HTTP_SERVER_PORT')),
            'sock_type' => SWOOLE_SOCK_TCP,
            'callbacks' => [
                Event::ON_REQUEST => [Hyperf\HttpServer\Server::class, 'onRequest'],
            ],
        ],
        [
            'name' => 'ws',
            'type' => Server::SERVER_WEBSOCKET,
            'host' => '0.0.0.0',
            'port' => intval(\Hyperf\Support\env('WEBSOCKET_SERVER_PORT')),
            'sock_type' => SWOOLE_SOCK_TCP,
            'callbacks' => [
                Event::ON_HAND_SHAKE => [Hyperf\WebSocketServer\Server::class, 'onHandShake'],
                Event::ON_MESSAGE => [Hyperf\WebSocketServer\Server::class, 'onMessage'],
                Event::ON_CLOSE => [Hyperf\WebSocketServer\Server::class, 'onClose'],
            ],
        ],
    ],
    'settings' => [
        Constant::OPTION_ENABLE_COROUTINE    => true,
        Constant::OPTION_WORKER_NUM          => swoole_cpu_num(),
        Constant::OPTION_PID_FILE            => BASE_PATH . '/runtime/hyperf.pid',
        Constant::OPTION_OPEN_TCP_NODELAY    => true,
        Constant::OPTION_MAX_COROUTINE       => 100000,
        Constant::OPTION_OPEN_HTTP2_PROTOCOL => true,
        Constant::OPTION_MAX_REQUEST         => 100000,
        Constant::OPTION_SOCKET_BUFFER_SIZE  => 2 * 1024 * 1024,
        Constant::OPTION_BUFFER_OUTPUT_SIZE  => 2 * 1024 * 1024,

        Constant::OPTION_DOCUMENT_ROOT         => BASE_PATH . '/public',
        Constant::OPTION_ENABLE_STATIC_HANDLER => true,
        Constant::OPTION_PACKAGE_MAX_LENGTH    => 256 * 1024 * 1024,

        Constant::OPTION_TASK_WORKER_NUM       => swoole_cpu_num() * 2,
        Constant::OPTION_TASK_ENABLE_COROUTINE => false,
    ],
    'callbacks' => [
        Event::ON_WORKER_START => [Hyperf\Framework\Bootstrap\WorkerStartCallback::class, 'onWorkerStart'],
        Event::ON_PIPE_MESSAGE => [Hyperf\Framework\Bootstrap\PipeMessageCallback::class, 'onPipeMessage'],
        Event::ON_WORKER_EXIT  => [Hyperf\Framework\Bootstrap\WorkerExitCallback::class, 'onWorkerExit'],
        Event::ON_TASK         => [Hyperf\Framework\Bootstrap\TaskCallback::class, 'onTask'],
        Event::ON_FINISH       => [Hyperf\Framework\Bootstrap\FinishCallback::class, 'onFinish'],
    ],
];
