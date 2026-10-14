<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Tell browsers not to keep pages in their cache or back/forward history.
 *
 * Pressing Back then asks the server again instead of showing a stored copy, so:
 *  - after signing in, Back to the login page redirects to home (guest pages bounce
 *    signed-in members, and the admin login bounces admins to the dashboard), and the
 *    username and password typed there are never shown again;
 *  - after signing out, Back can't show account pages from memory.
 */
class PreventBackHistory
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($response instanceof BinaryFileResponse || $response instanceof StreamedResponse) {
            return $response;
        }

        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0, private');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', 'Sat, 01 Jan 2000 00:00:00 GMT');

        return $response;
    }
}
