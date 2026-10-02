<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Not logged in and opens a protected page -> go to the login page
        $middleware->redirectGuestsTo(fn () => route('login'));

        // Already logged in and opens the login/register page -> go to the dashboard of the role
        $middleware->redirectUsersTo(fn (Request $request) => $request->user()->homeUrl());
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
