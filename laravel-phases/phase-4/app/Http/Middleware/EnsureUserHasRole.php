<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guard for each area of the system (capstone FR-REQ002, NFR-REQ011).
 *
 * Used in routes/web.php like:  ->middleware('role:staff')
 *   - not logged in            -> login page (handled by 'auth' before this)
 *   - account was deactivated  -> logged out right away
 *   - wrong role               -> 403 "Access Denied" page
 */
class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        // The Super Admin may deactivate an account while that person is logged in
        if (! $user->isActive()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->withErrors(['email' => 'Your account is inactive. Please contact FMH Animal Clinic.']);
        }

        if (! $user->hasRole(...$roles)) {
            abort(403, 'This page is not part of your account.');
        }

        return $next($request);
    }
}
