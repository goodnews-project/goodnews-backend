<?php

declare(strict_types=1);

use function Hyperf\Support\env;

return [
    'key_store_path'     => 'storage',
    'client_uuids'       => true,
    'key'                => 'CpmLVtjV8diGbhEsVD3IWoVOn31pRpmupEcxMCgtXp9LGpe39F',
    'token_days'         => null,
    'refresh_token_days' => null,
    'person_token_days'  => null,
    'private_key'        => BASE_PATH.'/storage/oauth-private.key',
    'public_key'         => BASE_PATH.'/storage/oauth-public.key',
    'storage'            => [
        'database' => [
            'connection' => env('DB_CONNECTION', 'default'),
        ],
    ],
];
