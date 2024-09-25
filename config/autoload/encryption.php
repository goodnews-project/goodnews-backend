<?php

declare(strict_types=1);

use function Hyperf\Support\env;
/**
 * This file is part of hyperf-ext/encryption.
 *
 * @link     https://github.com/hyperf-ext/encryption
 * @contact  eric@zhu.email
 * @license  https://github.com/hyperf-ext/encryption/blob/master/LICENSE
 */
return [
    'default' => 'aes',

    'driver' => [
        'aes' => [
            'class' => \HyperfExt\Encryption\Driver\AesDriver::class,
            'options' => [
                'key' => env('AES_KEY', 'e5gXiqTZ6s2yzYeSz635lg=='),
                'cipher' => env('AES_CIPHER', 'AES-128-CBC'),
            ],
        ],
    ],
];
