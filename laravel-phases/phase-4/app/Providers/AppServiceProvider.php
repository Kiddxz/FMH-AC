<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
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
        // ---------- Permissions (Phase 4) ----------
        // Every permission slug from the database (e.g. 'pos.manage') works as a Laravel ability:
        //   routes:  ->middleware('can:pos.manage')
        //   Blade:   @can('pos.manage') ... @endcan
        // If the role has the permission -> allowed. Otherwise Laravel continues to the
        // policies (app/Policies), and anything not allowed there is denied.
        Gate::before(function (User $user, string $ability) {
            return $user->hasPermission($ability) ? true : null;
        });

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
