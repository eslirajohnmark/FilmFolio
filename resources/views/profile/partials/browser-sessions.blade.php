{{-- $sessions  from App\Services\BrowserSessions::forUser(); $sessionsTracked  bool --}}
<section class="panel">
    <h2>Browser sessions</h2>
    <p class="panel-sub">
        Devices that are signed in to your account. If you don't recognise one, sign it out and change your password.
        You're signed out automatically after {{ config('session.lifetime') }} minutes without activity.
    </p>

    @if(! $sessionsTracked)
        <p class="empty-state">Session tracking needs <code>SESSION_DRIVER=database</code>.</p>
    @else
        <ul class="session-list">
            @foreach($sessions as $session)
                <li class="session-row">
                    <span class="session-icon" aria-hidden="true">
                        @if($session->mobile)
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="7" y="2.5" width="10" height="19" rx="2"/><path d="M11 18h2"/></svg>
                        @else
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="4" width="18" height="12" rx="2"/><path d="M8 20h8M12 16v4"/></svg>
                        @endif
                    </span>
                    <span class="session-info">
                        <b>{{ $session->platform }} · {{ $session->browser }}</b>
                        <span>
                            {{ $session->ip ?? 'Unknown IP' }} ·
                            @if($session->isCurrent)
                                <span class="session-current">This device</span>
                            @else
                                Last active {{ $session->lastActive->diffForHumans() }}
                            @endif
                        </span>
                    </span>
                    @unless($session->isCurrent)
                        <form method="POST" action="{{ route('sessions.destroy', $session->id) }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn-secondary">Sign out</button>
                        </form>
                    @endunless
                </li>
            @endforeach
        </ul>

        @if($sessions->where('isCurrent', false)->isNotEmpty())
            <form method="POST" action="{{ route('sessions.destroy-others') }}" class="panel-form" style="margin-top:18px;">
                @csrf
                @method('DELETE')
                <div class="field">
                    <label for="logout_others_password">Confirm your password to sign out every other device</label>
                    <input id="logout_others_password" name="password" type="password" autocomplete="current-password" required>
                    @error('password', 'logoutOtherSessions') <div class="field-error">{{ $message }}</div> @enderror
                </div>
                <div class="actions">
                    <button type="submit" class="btn btn-primary">Sign out other devices</button>
                </div>
            </form>
        @endif
    @endif
</section>
