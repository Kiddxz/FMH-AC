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
        // ---------- Limits for the login / register / password pages (Phase 3) ----------
        // Each limit has its own counter, per computer (IP address).

        // Login form: 10 submits per minute (wrong passwords are also limited per email in LoginController)
        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(10)->by('login|' . $request->ip()));

        // Register, verify code, reset password forms: 10 submits per minute
        RateLimiter::for('auth-forms', fn (Request $request) => Limit::perMinute(10)->by('auth-forms|' . $request->ip()));

        // Sending an email code (forgot password, "send a new code"): 3 per minute
        RateLimiter::for('send-code', fn (Request $request) => Limit::perMinute(3)->by('send-code|' . $request->ip()));
    }
}
