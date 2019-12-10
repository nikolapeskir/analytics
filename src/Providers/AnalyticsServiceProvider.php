<?php

namespace Leanmachine\Analytics\Providers;

use Illuminate\Support\ServiceProvider;

class AnalyticsServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../../config/analytics.php' => config_path('analytics.php', 'config'),
            __DIR__.'/../resources/views' => resource_path('views/vendor'),
        ]);

        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'analytics');
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/analytics.php', 'analytics');

        $this->app->make('Leanmachine\Analytics\Http\Controllers\AnalyticsController');

        $this->app->alias(Analytics::class, 'laravel-analytics');
    }
}
