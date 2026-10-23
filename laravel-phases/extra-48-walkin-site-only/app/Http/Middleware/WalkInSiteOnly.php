<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * The walk-in website (BOOKING_DOMAIN, e.g. fmh-walkin.test) shows ONLY the booking pages
 * and the pay link of a bill. Typing another address there (/login, /admin, /portal, /staff, ...)
 * gives "Page Not Found": the accounts and the clinic panels open only on the main website.
 */
class WalkInSiteOnly
{
    // Names of the pages allowed on the walk-in website (guest.* = booking, pay.* = online payment)
    private const ALLOWED = ['guest.', 'pay.'];

    public function handle(Request $request, Closure $next): Response
    {
        $domain = config('clinic.booking_domain');

        if ($domain && strcasecmp($request->getHost(), $domain) === 0) {
            if (! Str::startsWith((string) $request->route()?->getName(), self::ALLOWED)) {
                abort(404);
            }
        }

        return $next($request);
    }
}
