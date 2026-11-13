<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
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
        // Limits for the public QR pages. They are generous because many mobile
        // users can share one IP address. Codes are random, so guessing them
        // is hopeless anyway; this just blocks abusive scripts.
        RateLimiter::for('redeem', fn (Request $request) => Limit::perMinute(60)->by($request->ip()));
        RateLimiter::for('redeem-submit', fn (Request $request) => Limit::perMinute(10)->by($request->ip()));
    }
}
