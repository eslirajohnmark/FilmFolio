<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AdminLoginController extends Controller
{
    /**
     * Failed attempts allowed per username + IP before a lockout.
     */
    public const MAX_ATTEMPTS = 5;

    public function show(): View|RedirectResponse
    {
        if (Auth::guard('admin')->check()) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.auth.login');
    }

    /**
     * Sign in through the `admin` guard, which only knows the `admins` table:
     * member accounts can never reach the admin panel.
     */
    public function login(Request $request): RedirectResponse
    {
        $request->validate([
            'login' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ]);

        $login = trim($request->string('login'));
        $throttleKey = 'admin-login|'.Str::transliterate(Str::lower($login)).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_ATTEMPTS)) {
            event(new Lockout($request));

            throw ValidationException::withMessages([
                'login' => trans('auth.throttle', [
                    'seconds' => $seconds = RateLimiter::availableIn($throttleKey),
                    'minutes' => ceil($seconds / 60),
                ]),
            ]);
        }

        $credentials = [str_contains($login, '@') ? 'email' : 'username' => $login, 'password' => $request->string('password')->toString()];

        if (! Auth::guard('admin')->attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::hit($throttleKey);
            Log::warning("Failed admin sign-in for \"{$login}\" from {$request->ip()}.");

            throw ValidationException::withMessages([
                'login' => LoginRequest::FAILED_MESSAGE,
            ]);
        }

        RateLimiter::clear($throttleKey);
        $request->session()->regenerate();

        $admin = Auth::guard('admin')->user();
        $admin->forceFill(['last_login_at' => now()])->save();
        Log::info("Admin @{$admin->username} signed in from {$request->ip()}.");

        return redirect()->intended(route('admin.dashboard'))
            ->with('banner', ['type' => 'success', 'message' => 'Login Successful']);
    }

    /**
     * Sign the admin out without touching a member session in the same browser.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('admin')->logout();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login')->with('status', 'Signed out of the admin panel.');
    }
}
