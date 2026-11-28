<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // hours the owner set on the admin settings page win over config/restaurant.php
        \App\Models\Setting::forget();
        \App\Models\Setting::applyToConfig();
    }
}
