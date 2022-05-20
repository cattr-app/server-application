<?php

return [
    'dsn' => env('SENTRY_DSN'),

    'release' => config('app.version'),

    'breadcrumbs' => [

        // Capture bindings on SQL queries logged in breadcrumbs
        'sql_bindings' => true,

    ],

    'send_default_pii' => env('SENTRY_COLLECT_USERS', false),

    'traces_sample_rate' => (float) env('SENTRY_TRACES_SIMPLE_RATE', 0.2),
];
