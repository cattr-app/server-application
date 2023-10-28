<?php

use App\Helpers\Version;
use Illuminate\Support\Facades\Facade;

return [
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
    'stats_collector_url' => env('STATS_COLLECTOR_URL', 'https://stats.cattr.app'),
    'cipher' => 'AES-256-CBC',
    'recaptcha' => [
        'enabled' => env('RECAPTCHA_ENABLED', false)
    ],
    'maintenance' => [
        'driver' => 'file',
        // 'store'  => 'redis',
    ],
    'user_activity' => [
        'online_status_time' => 60,
        'heartbeat_period' => 30,
    ],
    'providers' => [

        /*
         * Laravel Framework Service Providers...
         */
        Illuminate\Auth\AuthServiceProvider::class,
        Illuminate\Broadcasting\BroadcastServiceProvider::class,
        Illuminate\Bus\BusServiceProvider::class,
        Illuminate\Cache\CacheServiceProvider::class,
        Illuminate\Foundation\Providers\ConsoleSupportServiceProvider::class,
        Illuminate\Cookie\CookieServiceProvider::class,
        Illuminate\Database\DatabaseServiceProvider::class,
        Illuminate\Encryption\EncryptionServiceProvider::class,
        Illuminate\Filesystem\FilesystemServiceProvider::class,
        Illuminate\Foundation\Providers\FoundationServiceProvider::class,
        Illuminate\Hashing\HashServiceProvider::class,
        Illuminate\Mail\MailServiceProvider::class,
        Illuminate\Notifications\NotificationServiceProvider::class,
        Illuminate\Pagination\PaginationServiceProvider::class,
        Illuminate\Pipeline\PipelineServiceProvider::class,
        Illuminate\Queue\QueueServiceProvider::class,
        Illuminate\Redis\RedisServiceProvider::class,
        Illuminate\Auth\Passwords\PasswordResetServiceProvider::class,
        Illuminate\Session\SessionServiceProvider::class,
        Illuminate\Translation\TranslationServiceProvider::class,
        Illuminate\Validation\ValidationServiceProvider::class,
        Illuminate\View\ViewServiceProvider::class,

        /*
         * Package Service Providers...
         */

        Intervention\Image\ImageServiceProvider::class,
        Sentry\Laravel\ServiceProvider::class,

        /*
         * Application Service Providers...
         */

        App\Providers\AppServiceProvider::class,
        App\Providers\AuthServiceProvider::class,
        App\Providers\BroadcastServiceProvider::class,
        App\Providers\EventServiceProvider::class,
        App\Providers\FilterServiceProvider::class,
        App\Providers\CatEventServiceProvider::class,
        App\Providers\RouteServiceProvider::class,

        App\Providers\SettingsServiceProvider::class,
        App\Providers\ScreenshotsServiceProvider::class,
    ],

    'aliases' => Facade::defaultAliases()->merge([
        'Sentry' => Sentry\Laravel\Facade::class,

        'Settings' => App\Facades\SettingsFacade::class,
        'Filter' => App\Facades\FilterFacade::class,
        'CatEvent' => App\Facades\EventFacade::class,

        'Responder' => Flugg\Responder\Facades\Responder::class,
        'Transformation' => Flugg\Responder\Facades\Transformation::class,
    ])->toArray(),
];
