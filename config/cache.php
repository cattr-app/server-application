<?php

return [
    'default' => env('CACHE_DRIVER', 'file'),

    'stores' => [
        'array' => [
            'driver' => 'array',
        ],

        'database' => [
            'driver' => 'database',
            'table' => 'cache',
            'connection' => null,
        ],

        'file' => [
            'driver' => 'file',
            'path' => storage_path('framework/cache/data'),
        ],

        'redis' => [
            'driver' => 'redis',
            'connection' => 'default',
        ],

        'octane' => ['driver' => 'octane'],
    ],

    'prefix' => preg_replace('#[^a-zA-Z0-9_\-]#', '_', env('APP_NAME', 'laravel')),

    'role_caching_ttl' => 3600,
];
