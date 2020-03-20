<?php

namespace Modules\CompanyManagement\Providers;

use Config;
use Illuminate\Database\Eloquent\Factory;
use Illuminate\Support\ServiceProvider;

class CompanyManagementServiceProvider extends ServiceProvider
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
        $langPath = resource_path('lang/modules/companymanagement');

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, 'companymanagement');
        } else {
            $this->loadTranslationsFrom(__DIR__ . '/../Resources/lang', 'companymanagement');
        }
    }

    /**
     * Register config.
     */
    protected function registerConfig(): void
    {
        $this->publishes([
            __DIR__ . '/../Config/config.php' => config_path('companymanagement.php'),
        ], 'config');
        $this->mergeConfigFrom(
            __DIR__ . '/../Config/config.php',
            'companymanagement'
        );
    }

    /**
     * Register views.
     */
    public function registerViews(): void
    {
        $viewPath = resource_path('views/modules/companymanagement');

        $sourcePath = __DIR__ . '/../Resources/views';

        $this->publishes([
            $sourcePath => $viewPath
        ], 'views');

        $this->loadViewsFrom(array_merge(array_map(static function ($path) {
            return $path . '/modules/companymanagement';
        }, Config::get('view.paths')), [$sourcePath]), 'companymanagement');
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
        $this->app->register(RouteServiceProvider::class);
    }
}
