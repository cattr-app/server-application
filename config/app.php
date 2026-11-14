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
    // Offline screenshot archives (offline-sync/upload-screenshots) are validated within the upload request, stored on
    // the default (shared) disk and attached by a queue job. Archives above these limits are rejected before that.
    'offline_screenshots' => [
        // 1000 entries ≈ 10 working days of 96 screenshots; a job needs ~75 ms per screenshot on the local disk and
        // ~310 ms on S3, i.e. up to ~5 min, within the job timeout.
        'max_files' => (int) env('OFFLINE_SCREENSHOTS_MAX_FILES', 1000),
        // Total uncompressed size of all archive entries
        'max_size' => (int) env('OFFLINE_SCREENSHOTS_MAX_SIZE_BYTES', 1024 * 1024 * 1024),
        // Seconds a queue worker lets one attempt of the job run. Keep it below QUEUE_RETRY_AFTER (960): the job
        // resumes after the entries already processed, so a timed-out attempt is continued by the next one.
        'job_timeout' => (int) env('OFFLINE_SCREENSHOTS_JOB_TIMEOUT', 900),
        'job_tries' => (int) env('OFFLINE_SCREENSHOTS_JOB_TRIES', 3),
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
