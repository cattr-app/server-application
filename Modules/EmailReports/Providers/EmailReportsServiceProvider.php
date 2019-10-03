<?php

namespace Modules\EmailReports\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Factory;

class EmailReportsServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Boot the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerConsoleCommands();
        $this->registerViews();
        $this->loadEmailreportsRules();
        $this->registerFactories();
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Register config.
     *
     * @return void
     */
    protected function registerConfig()
    {
        $this->publishes([
            __DIR__.'/../Config/config.php' => config_path('emailreports.php'),
        ], 'config');
        $this->mergeConfigFrom(
            __DIR__.'/../Config/config.php', 'emailreports'
        );
    }

    /**
     * Register views.
     *
     * @return void
     */
    public function registerViews()
    {
        $sourcePath = __DIR__.'/../Resources/views';
        $viewFactory = $this->app->make(\Illuminate\View\Factory::class);
        $viewFactory->addLocation($sourcePath);
        $viewFactory->addNamespace('emailreports', $sourcePath . '/emailreports');
    }

    /**
     * Register translations.
     *
     * @return void
     */
    public function registerTranslations()
    {
        $langPath = resource_path('lang/modules/emailreports');

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, 'emailreports');
        } else {
            $this->loadTranslationsFrom(__DIR__ .'/../Resources/lang', 'emailreports');
        }
    }

    /**
     * Register an additional directory of factories.
     * 
     * @return void
     */
    public function registerFactories()
    {
        if (! app()->environment('production')) {
            app(Factory::class)->load(__DIR__ . '/../Database/factories');
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

    private function registerConsoleCommands()
    {
        $this->commands([
            'Modules\EmailReports\Console\EmailReportsSender'
        ]);
    }

    private function loadEmailreportsRules()
    {
        \Filter::listen('role.actions.list', static function ($rules) {
            if (!isset($rules['email-reports'])) {
                $rules['email-reports'] = [
                    'list' => __('Email Reports list'),
                    'show' => __('Email Reports show'),
                    'edit' => __('Email Reports edit'),
                    'remove' => __('Email Reports remove'),
                    'create' => __('Email Reports create'),
                    'count' => __('Email Reports count'),
                ];
            }

            return $rules;
        });
    }
}
