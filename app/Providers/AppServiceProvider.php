<?php

namespace App\Providers;

use App;
use App\Models\Invitation;
use App\Models\User;
use App\Models\Screenshot;
use App\Observers\InvitationObserver;
use App\Observers\UserObserver;
use App\Observers\ScreenshotObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (App::environment(['demo', 'staging'])) {
            $this->app->bind(
                App\Contracts\ScreenshotService::class,
                App\Services\DemoScreenshotService::class
            );
        } else {
            $this->app->bind(
                App\Contracts\ScreenshotService::class,
                App\Services\ScreenshotService::class
            );
        }

        $this->app->bind(
            App\Contracts\Settings::class,
            App\Services\SettingsService::class
        );

        User::observe(UserObserver::class);
        Invitation::observe(InvitationObserver::class);
        Screenshot::observe(ScreenshotObserver::class);
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        if (config('app.debug') && App::environment(['local', 'staging'])) {
            $this->app->register(\Laravel\Telescope\TelescopeServiceProvider::class);
            $this->app->register(TelescopeServiceProvider::class);
        }
    }
}
