<?php

namespace Modules\EmailReports\Providers;

use App\EventFilter\Facades\Filter;
use Illuminate\Support\ServiceProvider;
use Modules\EmailReports\Console\EmailReportsSender;

/**
 * Class EmailReportsServiceProvider
 * @package Modules\EmailReports\Providers
 */
class EmailReportsServiceProvider extends ServiceProvider
{

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        $this->registerConfig();
        $this->registerCommands();
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'emailreports');

        Filter::listen('role.actions.list', static function ($rules) {
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

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);
    }

    /**
     * Register config.
     */
    protected function registerConfig(): void
    {
        $this->publishes([
            __DIR__.'/../Config/config.php' => config_path('emailreports.php'),
        ], 'config');
        $this->mergeConfigFrom(
            __DIR__.'/../Config/config.php', 'emailreports'
        );
    }

    private function registerCommands(): void
    {
        $this->commands([
            EmailReportsSender::class,
        ]);
    }
}
