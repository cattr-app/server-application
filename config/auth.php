<?php

return [
    'defaults' => [
        'guard' => 'api',
        'passwords' => 'users',
    ],

    'guards' => [
        'api' => [
            'driver' => 'jwt',
            'provider' => 'users',
        ],
        'web' => [
            'driver' => 'session',
            'provider' => 'users'
        ]
    ],

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => App\Models\User::class,
        ],
    ],

    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table' => 'password_resets',
            'expire' => 60,
        ],
    ],

    'cattr-client-agent' => 'Cattr\-Desktop\/v*',

    'lifetime_minutes' => [
        'jwt' => env('AUTH_JWT_LIFETIME_MINUTES', 365 * 24 * 60),
        'desktop_token' => env('AUTH_DESKTOP_TOKEN_LIFETIME_MINUTES', 10),
    ],
];
