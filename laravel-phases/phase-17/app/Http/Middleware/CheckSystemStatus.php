<?php

namespace App\Http\Middleware;

use App\Models\Role;
use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Maintenance mode (Phase 17, capstone SCOPE-14). When the Super Admin turns it on in Settings,
 * everybody else sees the "Under Maintenance" page. The home page, the login page and logging out
 * still work, so the Super Admin can log in and turn it off again.
 */
class CheckSystemStatus
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Setting::inMaintenance()
            || $request->user()?->hasRole(Role::SUPER_ADMIN)
            || $request->routeIs('home', 'login', 'login.attempt', 'logout')) {
            return $next($request);
        }

        return response()->view('errors.503', ['message' => Setting::get('maintenance_message')], 503);
    }
}
