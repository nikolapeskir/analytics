<?php

namespace Leanmachine\Analytics\Providers;

use Illuminate\Support\ServiceProvider;

class AnalyticsServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../../config/analytics.php' => config_path('analytics.php'),
        ]);

        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        // $this->loadMigrationsFrom(__DIR__.'/migrations');
        // $this->loadViewsFrom(__DIR__.'/views', 'analytics');
        // $this->publishes([
        //     __DIR__.'/views' => resource_path('views/vendor/analytics'),
        // ]);
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/analytics.php', 'analytics');

        $this->app->make('Leanmachine\Analytics\Http\Controllers\AnalyticsController');

        // $config = config('analytics');
        // echo $config['routes'];
    }
}