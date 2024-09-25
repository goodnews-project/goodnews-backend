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
use function Hyperf\Support\env;

return [
    'default' => [
        'enable' => true,
        'host' => env('NSQ_HOST', '127.0.0.1'),
        'port' => (int) env('NSQ_PORT', 4150),
        'pool' => [
            'min_connections' => 1,
            'max_connections' => 10,
            'connect_timeout' => 10.0,
            'wait_timeout' => 3.0,
            'heartbeat' => -1,
            'max_idle_time' => 30.0,
        ],
        'nsqd' => [
            'port' => (int) env('NSQD_PORT', 4151),
            'options' => [
            ],
        ],
    ],
];
