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
        // customer sign-in codes: MSG91 when OTP_DRIVER=msg91, otherwise the log (testing)
        $this->app->bind(\App\Services\Otp\OtpSender::class, fn () => config('otp.driver') === 'msg91'
            ? new \App\Services\Otp\Msg91OtpSender
            : new \App\Services\Otp\LogOtpSender);
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
