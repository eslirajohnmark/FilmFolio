<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\BrowserSessions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountController extends Controller
{
    /**
     * Suspension lengths offered to admins, in days (0 = until lifted).
     *
     * @var array<int, string>
     */
    public const SUSPENSION_OPTIONS = [1 => '1 day', 3 => '3 days', 7 => '1 week', 30 => '30 days', 0 => 'Until lifted'];

    public function index(): View
    {
        return view('admin.accounts.index');
    }

    public function show(User $user, BrowserSessions $sessions): View
    {
        $user->loadCount(['reviews', 'diaryEntries', 'filmLists', 'followers', 'following']);

        return view('admin.accounts.show', [
            'user' => $user,
            'activities' => $user->activities()->with(['user', 'subject'])->visibleInFeeds()->latest()->take(15)->get(),
            'reviews' => $user->reviews()->with(['film', 'user'])->withCount('comments')->latest()->take(10)->get(),
            'lists' => $user->filmLists()->withCount('films')->latest()->take(10)->get(),
            'suspensionOptions' => self::SUSPENSION_OPTIONS,
            'sessions' => $sessions->forUser($user),
        ]);
    }

    /**
     * End every session the member has and invalidate their "remember me" cookies.
     */
    public function signOutEverywhere(User $user, BrowserSessions $sessions): RedirectResponse
    {
        $count = $sessions->destroy($user);
        $sessions->forgetRememberedDevices($user);

        return back()->with('status', "@{$user->username} was signed out of {$count} ".str('session')->plural($count).'.');
    }

    public function suspend(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'days' => ['required', 'integer', 'in:'.implode(',', array_keys(self::SUSPENSION_OPTIONS))],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $user->forceFill([
            'suspended_at' => now(),
            'suspended_until' => $data['days'] > 0 ? now()->addDays((int) $data['days']) : null,
            'moderation_reason' => $data['reason'] ?? null,
        ])->save();

        return back()->with('status', "@{$user->username} suspended ".($data['days'] > 0 ? "for {$data['days']} day(s)." : 'until lifted.'));
    }

    public function ban(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:500']]);

        $user->forceFill([
            'banned_at' => now(),
            'moderation_reason' => $data['reason'] ?? null,
        ])->save();

        return back()->with('status', "@{$user->username} banned.");
    }

    /**
     * Lift any suspension or ban.
     */
    public function reinstate(User $user): RedirectResponse
    {
        $user->forceFill([
            'suspended_at' => null,
            'suspended_until' => null,
            'banned_at' => null,
            'moderation_reason' => null,
        ])->save();

        return back()->with('status', "@{$user->username} reinstated.");
    }

    public function toggleFeatured(User $user): RedirectResponse
    {
        $user->forceFill(['is_featured' => ! $user->is_featured])->save();

        return back()->with('status', $user->is_featured ? "@{$user->username} is now a featured member." : "@{$user->username} is no longer featured.");
    }

    public function destroy(User $user): RedirectResponse
    {
        $user->delete();

        return redirect()->route('admin.accounts.index')->with('status', "Account @{$user->username} deleted.");
    }
}
