<?php

namespace Leanmachine\Analytics\Providers;

use Illuminate\Support\ServiceProvider;

class AnalyticsServiceProvider extends ServiceProvider
{
    public function boot()
    {
        echo "package loaded";
    }

    public function register()
    {

    }
}