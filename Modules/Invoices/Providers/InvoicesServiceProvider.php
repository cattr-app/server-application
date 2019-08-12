<?php

namespace Modules\Invoices\Providers;

use App\EventFilter\EventServiceProvider as ServiceProvider;
use Illuminate\Database\Eloquent\Factory;

class InvoicesServiceProvider extends ServiceProvider
{
    /**
     * Boot the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->registerFactories();
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        $this->loadInvoicesRules();

        parent::boot();
    }

    /**
     * Register an additional directory of factories.
     * @source https://github.com/sebastiaanluca/laravel-resource-flow/blob/develop/src/Modules/ModuleServiceProvider.php#L66
     */
    public function registerFactories()
    {
        if (!app()->environment('production')) {
            app(Factory::class)->load(__DIR__ . '/../Database/factories');
        }
    }

    /**
     * Register config.
     *
     * @return void
     */
    protected function registerConfig()
    {
        $this->publishes([
            __DIR__ . '/../Config/config.php' => config_path('invoices.php'),
        ], 'config');
        $this->mergeConfigFrom(
            __DIR__ . '/../Config/config.php', 'invoices'
        );
    }

    /**
     * Register views.
     *
     * @return void
     */
    public function registerViews()
    {
        $viewPath = resource_path('views/modules/invoices');

        $sourcePath = __DIR__ . '/../Resources/views';

        $this->publishes([
            $sourcePath => $viewPath
        ], 'views');

        $this->loadViewsFrom(array_merge(array_map(function ($path) {
            return $path . '/modules/invoices';
        }, \Config::get('view.paths')), [$sourcePath]), 'invoices');
    }

    /**
     * Register translations.
     *
     * @return void
     */
    public function registerTranslations()
    {
        $langPath = resource_path('lang/modules/invoices');

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, 'invoices');
        } else {
            $this->loadTranslationsFrom(__DIR__ . '/../Resources/lang', 'invoices');
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [];
    }

    private function loadInvoicesRules()
    {
        \Filter::listen('role.actions.list', static function ($data) {
             $data['invoices'] = [
                'list' => __('Invoices list'),
                'full_access' => __('Invoices full access'),
            ];

            return $data;
        });
    }
}
