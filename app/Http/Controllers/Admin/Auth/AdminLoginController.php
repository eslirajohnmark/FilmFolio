<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\Activity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AdminLoginController extends Controller
{
    public function show(): View|RedirectResponse
    {
        if (Auth::check() && Auth::user()->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.auth.login');
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $user = Auth::user();

        if (! $user->isAdmin()) {
            Auth::logout();
            throw ValidationException::withMessages([
                'login' => 'This account does not have administrator access.',
            ]);
        }

        $request->session()->regenerate();
        Activity::log($user, 'admin_login', $user);

        return redirect()->intended(route('admin.dashboard'))
            ->with('status', 'Welcome back, admin.');
    }
}
