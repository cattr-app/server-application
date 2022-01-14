<?php

namespace App\Providers;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;
use App\Services\SettingsProviderService;
use App\Contracts\SettingsProvider;

class SettingsServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register(): void
    {
        $this->app->bind(
            SettingsProvider::class,
            SettingsProviderService::class
        );

        $this->app->bind('settings', function () {
            return $this->app->makeWith(SettingsProvider::class, ['saveScope' => false]);
        });
    }

    public function provides(): array
    {
        return [SettingsProvider::class, 'settings'];
    }
}
