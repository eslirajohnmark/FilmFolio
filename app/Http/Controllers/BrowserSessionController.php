<?php

namespace App\Http\Controllers;

use App\Services\BrowserSessions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Members signing their account out of other devices (Settings → Browser sessions).
 */
class BrowserSessionController extends Controller
{
    public function __construct(protected BrowserSessions $sessions) {}

    /**
     * Sign out one other device.
     */
    public function destroy(Request $request, string $session): RedirectResponse
    {
        if ($session === $request->session()->getId()) {
            return back()->with('error', 'Use "Sign out" in the account menu to end this session.');
        }

        $removed = $this->sessions->destroy($request->user(), sessionId: $session);

        if ($removed) {
            $this->sessions->forgetRememberedDevices($request->user());
        }

        return $removed
            ? back()->with('status', 'That device has been signed out.')
            : back()->with('error', 'That session had already ended.');
    }

    /**
     * Sign out every other device after confirming the password.
     */
    public function destroyOthers(Request $request): RedirectResponse
    {
        $request->validateWithBag('logoutOtherSessions', [
            'password' => ['required', 'current_password'],
        ]);

        Auth::logoutOtherDevices($request->input('password'));
        $this->sessions->destroy($request->user(), exceptSessionId: $request->session()->getId());

        return back()->with('status', 'Signed out of all your other devices.');
    }
}
