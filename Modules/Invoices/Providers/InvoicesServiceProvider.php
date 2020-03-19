<?php

namespace Modules\Invoices\Providers;

use App\EventFilter\EventServiceProvider as ServiceProvider;
use App\EventFilter\Facades\Filter;
use Config;
use Illuminate\Database\Eloquent\Factory;

class InvoicesServiceProvider extends ServiceProvider
{
    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        $this->registerConfig();
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        parent::boot();
        Filter::listen('role.actions.list', static function ($data) {
            $data['invoices'] = [
                'list' => __('Invoices list'),
            ];

            return $data;
        });
    }

    /**
     * Register config.
     */
    protected function registerConfig(): void
    {
        $this->publishes([
            __DIR__ . '/../Config/config.php' => config_path('invoices.php'),
        ], 'config');
        $this->mergeConfigFrom(
            __DIR__ . '/../Config/config.php',
            'invoices'
        );
    }

    /**
     * Register the service provider.
     */
    public function register():void
    {
        $this->app->register(RouteServiceProvider::class);
    }

    /**
     * Register an additional directory of factories.
     * @source https://github.com/sebastiaanluca/laravel-resource-flow/blob/develop/src/Modules/ModuleServiceProvider.php#L66
     */
    public function registerFactories(): void
    {
        if (!app()->environment('production')) {
            app(Factory::class)->load(__DIR__ . '/../Database/factories');
        }
    }

    /**
     * Register views.
     */
    public function registerViews(): void
    {
        $viewPath = resource_path('views/modules/invoices');

        $sourcePath = __DIR__ . '/../Resources/views';

        $this->publishes([
            $sourcePath => $viewPath
        ], 'views');

        $this->loadViewsFrom(array_merge(array_map(static function ($path) {
            return $path . '/modules/invoices';
        }, Config::get('view.paths')), [$sourcePath]), 'invoices');
    }

    /**
     * Register translations.
     */
    public function registerTranslations(): void
    {
        $langPath = resource_path('lang/modules/invoices');

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, 'invoices');
        } else {
            $this->loadTranslationsFrom(__DIR__ . '/../Resources/lang', 'invoices');
        }
    }
}
