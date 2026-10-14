<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    /**
     * Admin pages need an administrator signed in through the `admin` guard.
     * The default guard stays `web` (member components on admin pages expect a User),
     * so admin code asks for the admin explicitly: auth('admin')->user().
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::guard('admin')->check()) {
            return redirect()->guest(route('admin.login'));
        }

        return $next($request);
    }
}
