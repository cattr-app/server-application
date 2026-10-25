<?php

/*
 * S3-compatible storage (AWS S3, MinIO, ...). Every S3-backed disk shares the bucket and credentials;
 * the "root" keeps the same layout as storage/app: screenshots/, attachments/, reports/.
 */
$s3Disk = static fn (string $root = '') => [
    'driver' => 's3',
    'key' => env('AWS_ACCESS_KEY_ID'),
    'secret' => env('AWS_SECRET_ACCESS_KEY'),
    'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    'bucket' => env('AWS_BUCKET'),
    'url' => env('AWS_URL'),
    // Presigned links are signed for this host (SigV4 signs "Host"), so prefer an endpoint that both the
    // app and browsers reach under the same name, e.g. the public MinIO URL.
    'endpoint' => env('AWS_ENDPOINT'),
    // Only rewrites scheme, host and port of an already signed link (path ignored). Use it only for a CDN /
    // proxy that forwards requests to the storage with the original AWS_ENDPOINT Host, or signatures fail.
    'temporary_url' => env('AWS_TEMPORARY_URL'),
    // MinIO and most self-hosted S3 implementations need path-style URLs.
    'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
    'root' => trim(trim((string) env('AWS_ROOT', ''), '/') . '/' . $root, '/'),
    'visibility' => 'private',
    'throw' => false,
    'report' => false,
];

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application. Just store away!
    |
    */

    // Screenshots and thumbnails live on the default disk: "local" (PVC / storage/app) or "s3".
    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Here you may configure as many filesystem "disks" as you wish, and you
    | may even configure multiple disks of the same driver. Defaults have
    | been set up for each driver as an example of the required values.
    |
    | Supported Drivers: "local", "ftp", "sftp", "s3"
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
            'throw' => false,
            'report' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => '/storage',
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ],

        // ATTACHMENTS_DISK: "local" (storage/app/attachments) or "s3" (<AWS_ROOT>/attachments).
        'attachments' => (env('ATTACHMENTS_DISK', 'local') === 's3' ? $s3Disk('attachments') : [
            'driver' => 'local',
            'root' => storage_path('app/attachments'),
            'throw' => false,
        ]) + ['checksum_algo' => 'sha512/256'],

        // REPORTS_DISK: "local" (storage/app/public/reports, served at /storage) or "s3" (<AWS_ROOT>/reports).
        'reports' => env('REPORTS_DISK', 'local') === 's3' ? $s3Disk() : [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => '/storage',
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ],

        's3' => $s3Disk(),

    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Here you may configure the symbolic links that will be created when the
    | `storage:link` Artisan command is executed. The array keys should be
    | the locations of the links and the values should be their targets.
    |
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
