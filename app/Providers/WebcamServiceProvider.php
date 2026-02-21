<?php

namespace App\Providers;

use App\Contracts\WebcamScreenshotService;
use App\Services\ProductionWebcamScreenshotService;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class WebcamServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register(): void
    {
        $this->app->bind(
            WebcamScreenshotService::class,
            ProductionWebcamScreenshotService::class
        );
    }

    public function provides(): array
    {
        return [WebcamScreenshotService::class];
    }
}
