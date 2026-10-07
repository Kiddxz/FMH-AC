<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        // Online payment with PayMongo (pay link for walk-in customers)
        then: function () {
            Route::middleware('web')->group(base_path('routes/online-payment.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // 'role' guard used in routes/web.php, e.g. ->middleware('role:staff')   (Phase 4)
        $middleware->alias([
            'role' => \App\Http\Middleware\EnsureUserHasRole::class,
        ]);

        // Maintenance mode from the Super Admin settings (Phase 17) and the security headers (Phase 18): run on every web page
        $middleware->web(append: [
            \App\Http\Middleware\CheckSystemStatus::class,
            \App\Http\Middleware\SecurityHeaders::class,
        ]);

        // Minimalist look: colored emoji on every page are sent as simple line icons
        $middleware->append(\App\Http\Middleware\EmojiToLineIcons::class);

        // Not logged in and opens a protected page -> go to the login page
        $middleware->redirectGuestsTo(fn () => route('login'));

        // Already logged in and opens the login/register page -> go to the dashboard of the role
        $middleware->redirectUsersTo(fn (Request $request) => $request->user()->homeUrl());
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
