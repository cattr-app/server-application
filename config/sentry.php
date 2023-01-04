<?php

return [
    'dsn' => env('SENTRY_DSN'),

    'release' => config('app.version'),

    'environment' => config('app.env'),

    'breadcrumbs' => [

        // Capture Laravel logs in breadcrumbs
        'logs' => true,

        // Capture SQL queries in breadcrumbs
        'sql_queries' => true,

        // Capture bindings on SQL queries logged in breadcrumbs
        'sql_bindings' => true,

        // Capture queue job information in breadcrumbs
        'queue_info' => true,

        // Capture command information in breadcrumbs
        'command_info' => true,

    ],

    'tracing' => [
        // Trace queue jobs as their own transactions
        'queue_job_transactions' => env('SENTRY_TRACE_QUEUE_ENABLED', false),

        // Capture queue jobs as spans when executed on the sync driver
        'queue_jobs' => true,

        // Capture SQL queries as spans
        'sql_queries' => true,

        // Try to find out where the SQL query originated from and add it to the query spans
        'sql_origin' => true,

        // Capture views as spans
        'views' => true,

        // Indicates if the tracing integrations supplied by Sentry should be loaded
        // See all default integration: https://github.com/getsentry/sentry-laravel/tree/master/src/Sentry/Laravel/Tracing/Integrations
        'default_integrations' => true,

        // Indicates that requests without a matching route should be traced
        'missing_routes' => false,
    ],

    'send_default_pii' => env('SENTRY_COLLECT_USERS', false),

    'traces_sample_rate' => (float) env('SENTRY_TRACES_SIMPLE_RATE', 0.2),
];
