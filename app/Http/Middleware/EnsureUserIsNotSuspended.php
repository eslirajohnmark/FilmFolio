<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsNotSuspended
{
    /**
     * Log out members who were suspended or banned while they had an active session.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($blocked = $request->user()?->accessBlockedMessage()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors(['login' => $blocked]);
        }

        return $next($request);
    }
}
