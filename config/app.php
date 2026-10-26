<?php

use App\Enums\ScreenshotsState;
use App\Helpers\Version;
use Illuminate\Support\Facades\Facade;

return [
    'screenshots_state' => env('SCREENSHOTS_STATE', ScreenshotsState::ANY->name),
    'name' => env('APP_NAME', 'Cattr'),
    'version' => (string)new Version(),
    'env' => env('APP_ENV', 'local'),
    'debug' => env('APP_DEBUG', false),
    'json_errors' => env('JSON_ERRORS', true),
    'url' => env('APP_URL', 'http://localhost'),
    'asset_url' => env('ASSET_URL'),
    'frontend_url' => env('FRONTEND_APP_URL'),
    'timezone' => date_default_timezone_get(),
    'languages' => ['en', 'ru'],
    'locale' => 'en',
    'fallback_locale' => 'en',
    'faker_locale' => 'en_US',
    'key' => env('APP_KEY'),
    'previous_keys' => [
        ...array_filter(
            explode(',', (string) env('APP_PREVIOUS_KEYS', ''))
        ),
    ],
    'stats_collector_url' => env('STATS_COLLECTOR_URL', 'https://stats.cattr.app'),
    'cipher' => 'AES-256-CBC',
    'recaptcha' => [
        'enabled' => env('RECAPTCHA_ENABLED', false)
    ],
    'maintenance' => [
        'driver' => 'file',
        'store' => env('APP_MAINTENANCE_STORE', 'database'),
        // 'store'  => 'redis',
    ],
    // Requests per minute and IP to the public signed screenshot routes (time-intervals/{id}/screenshot|thumb).
    // A timeline day of a user with 1-minute screenshots holds up to ~600 thumbnails (10 h of work).
    'screenshots_rate_limit' => (int) env('SCREENSHOTS_RATE_LIMIT', 600),
    // Offline screenshots (offline-sync/upload-screenshots) are processed within the upload request, which must
    // finish within Octane's max_execution_time (90 s). Larger archives are rejected before anything is extracted.
    // 150 entries ≈ 1.5 working days of 96 screenshots: ~11 s at ~75 ms per 1920×1080 screenshot on the local disk,
    // ~47 s at ~310 ms on S3, leaving headroom for extraction, queries and the response.
    'offline_screenshots' => [
        'max_files' => (int) env('OFFLINE_SCREENSHOTS_MAX_FILES', 150),
        // Total uncompressed size of all archive entries
        'max_size' => (int) env('OFFLINE_SCREENSHOTS_MAX_SIZE_BYTES', 1024 * 1024 * 1024),
    ],
    'user_activity' => [
        'online_status_time' => 60,
        'heartbeat_period' => 30,
    ],
    'aliases' => Facade::defaultAliases()->merge([
        'Settings' => App\Facades\SettingsFacade::class,
        'Filter' => App\Facades\FilterFacade::class,
        'CatEvent' => App\Facades\EventFacade::class,

        'Responder' => Flugg\Responder\Facades\Responder::class,
        'Transformation' => Flugg\Responder\Facades\Transformation::class,
    ])->toArray(),
];
