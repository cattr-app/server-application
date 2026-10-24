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
