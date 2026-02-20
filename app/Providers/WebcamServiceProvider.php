<?php

namespace App\Providers;

use App;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class WebcamServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register(): void
    {
        $this->app->bind(
            App\Contracts\WebcamScreenshotService::class,
            App\Services\ProductionWebcamScreenshotService::class
        );
    }

    public function provides(): array
    {
        return [App\Contracts\WebcamScreenshotService::class];
    }
}
