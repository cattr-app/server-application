<?php

namespace Modules\Reports\Providers;

use Config;
use Illuminate\Database\Eloquent\Factory;
use Illuminate\Support\ServiceProvider;
use Maatwebsite\Excel\ExcelServiceProvider;

class ReportsServiceProvider extends ServiceProvider
{
    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->registerFactories();
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
    }

    /**
     * Register translations.
     */
    public function registerTranslations(): void
    {
        $langPath = resource_path('lang/modules/reports');

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, 'reports');
        } else {
            $this->loadTranslationsFrom(__DIR__ . '/../Resources/lang', 'reports');
        }
    }

    /**
     * Register config.
     */
    protected function registerConfig(): void
    {
        $this->publishes([
            __DIR__ . '/../Config/config.php' => config_path('reports.php'),
        ], 'config');
        $this->mergeConfigFrom(
            __DIR__ . '/../Config/config.php',
            'reports'
        );
    }

    /**
     * Register views.
     */
    public function registerViews(): void
    {
        $viewPath = resource_path('views/modules/reports');

        $sourcePath = __DIR__ . '/../Resources/views';

        $this->publishes([
            $sourcePath => $viewPath
        ], 'views');

        $this->loadViewsFrom(array_merge(array_map(static function ($path) {
            return $path . '/modules/reports';
        }, Config::get('view.paths')), [$sourcePath]), 'reports');
    }

    /**
     * Register an additional directory of factories.
     */
    public function registerFactories(): void
    {
        if ($this->app->runningInConsole() && !app()->environment('production')) {
            app(Factory::class)->load(__DIR__ . '/../Database/factories');
        }
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->app->register(ExcelServiceProvider::class);
        $this->app->register(RouteServiceProvider::class);
    }
}
