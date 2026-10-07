<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;
use Throwable;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Email a password reset link to the member with this email address or username.
     *
     * The response is the same whether or not an account matches, so the form
     * can't be used to find out who is registered. The one exception is local
     * development with a Mailpit inbox configured, where a sent email takes the
     * member straight to that inbox for testing.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'string', 'max:255'],
        ]);

        $login = trim($request->string('email'));

        try {
            $status = Password::sendResetLink(str_contains($login, '@') ? ['email' => $login] : ['username' => $login]);
        } catch (Throwable $e) {
            Log::error("Password reset email could not be sent: {$e->getMessage()}");

            return back()->withInput($request->only('email'))
                ->withErrors(['email' => "We couldn't send the email right now. Please try again in a minute."]);
        }

        if ($status === Password::RESET_LINK_SENT && app()->isLocal() && filled(config('mail.mailpit_url'))) {
            return redirect()->away(config('mail.mailpit_url'));
        }

        return back()->with('status', 'If an account matches '.$login.', a password reset link is on its way. Check your inbox (and your spam folder).');
    }
}
