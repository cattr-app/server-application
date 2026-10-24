<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Metric namespace
    |--------------------------------------------------------------------------
    | Prefix applied to the metrics this application registers.
    */
    'namespace' => env('PROMETHEUS_NAMESPACE', ''),

    /*
    |--------------------------------------------------------------------------
    | Default metrics
    |--------------------------------------------------------------------------
    | Register the client's built-in php_info gauge.
    */
    'default_metrics' => true,

    /*
    |--------------------------------------------------------------------------
    | Storage adapter
    |--------------------------------------------------------------------------
    | memory (per process), apcu, or redis (shared between Octane workers).
    */
    'storage_adapter' => env('PROMETHEUS_STORAGE_ADAPTER', 'memory'),

    'storage_adapters' => [
        'redis' => [
            'host' => env('PROMETHEUS_REDIS_HOST', 'localhost'),
            'port' => (int) env('PROMETHEUS_REDIS_PORT', 6379),
            'password' => env('PROMETHEUS_REDIS_PASSWORD'),
            'database' => (int) env('PROMETHEUS_REDIS_DATABASE', 0),
            'timeout' => (float) env('PROMETHEUS_REDIS_TIMEOUT', 0.1),
            'read_timeout' => (float) env('PROMETHEUS_REDIS_READ_TIMEOUT', 10),
            'persistent_connections' => (bool) env('PROMETHEUS_REDIS_PERSISTENT_CONNECTIONS', false),
            'prefix' => env('PROMETHEUS_REDIS_PREFIX', 'PROMETHEUS_'),
        ],
    ],
];
