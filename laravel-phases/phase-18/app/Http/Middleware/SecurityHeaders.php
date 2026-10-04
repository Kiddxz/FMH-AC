<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Phase 18 (NFR-REQ011): extra protection added to every web page.
 *
 *  - Other websites cannot show our pages inside a frame (stops "clickjacking").
 *  - The browser must not guess file types (a file is only what the server says it is).
 *  - Links to other websites do not send the full address of our pages.
 *  - The pages cannot use the camera, microphone or location.
 *  - Pages seen while logged in are not saved by the browser, so pressing "Back"
 *    after logging out (or on a shared clinic computer) does not show patient records.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        $response->headers->set('Content-Security-Policy', "frame-ancestors 'self'; object-src 'none'; base-uri 'self'");

        // Only when the site runs on https (on the clinic server): always use https from now on
        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000');
        }

        if ($request->user()) {
            $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, private');
            $response->headers->set('Pragma', 'no-cache');
        }

        return $response;
    }
}
