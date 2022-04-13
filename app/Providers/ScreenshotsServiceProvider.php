<?php

namespace App\Providers;

use App;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class ScreenshotsServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register(): void
    {
        if (App::environment(['demo', 'staging'])) {
            $this->app->bind(
                App\Contracts\ScreenshotService::class,
                \Modules\Demo\Services\DemoScreenshotService::class
            );
        } else {
            $this->app->bind(
                App\Contracts\ScreenshotService::class,
                App\Services\ProductionScreenshotService::class
            );
        }
    }

    public function provides(): array
    {
        return [App\Contracts\ScreenshotService::class];
    }
}
