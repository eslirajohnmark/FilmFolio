@extends('layouts.admin')

@section('title', '@'.($user->username ?? $user->name))

@section('content')
@php $state = $user->accountStatus(); @endphp
<a href="{{ route('admin.accounts.index') }}" class="link" style="font-size:13px;">← All accounts</a>

<div class="profile-header" style="margin-top:14px;">
    <img src="{{ $user->avatarUrl() }}" class="profile-avatar" alt="">
    <div class="profile-info">
        <h1>{{ $user->name }}</h1>
        <div class="review-author" style="font-size:13px;">&#64;{{ $user->username }} · {{ $user->email }}</div>
        <p class="profile-bio">Joined {{ $user->created_at->format('M d, Y') }}</p>
        <span class="status status-{{ $state === 'active' ? 'active' : ($state === 'admin' ? 'admin' : ($state === 'banned' ? 'banned' : 'suspended')) }}">{{ ucfirst($state) }}</span>
        @if($user->is_featured) <span class="badge">Featured member</span> @endif
    </div>
    <div class="profile-stats" style="margin-left:auto;">
        <span><b>{{ $user->reviews_count }}</b> Reviews</span>
        <span><b>{{ $user->diary_entries_count }}</b> Logs</span>
        <span><b>{{ $user->film_lists_count }}</b> Lists</span>
        <span><b>{{ $user->followers_count }}</b> Followers</span>
    </div>
</div>

<div class="admin-split" style="margin-top:28px;grid-template-columns:minmax(0,1fr) 320px;">
    <div>
        <div class="section-label" style="margin-top:0;">Recent activity <a href="{{ route('profile.show', [$user, 'tab' => 'activity']) }}">Public profile ↗</a></div>
        @forelse($activities as $activity)
            @include('partials.activity-row', ['activity' => $activity])
        @empty
            <p class="empty-state">No activity.</p>
        @endforelse

        <div class="section-label">Reviews &amp; ratings</div>
        <div style="display:flex;flex-direction:column;gap:20px;">
            @forelse($reviews as $review)
                @include('partials.review-card', ['review' => $review])
            @empty
                <p class="empty-state">No reviews.</p>
            @endforelse
        </div>

        <div class="section-label">Lists</div>
        @forelse($lists as $list)
            <div class="diary-row">
                <a href="{{ route('lists.show', $list) }}" style="color:#fff;">{{ $list->name }}</a>
                <span class="text-muted">{{ $list->films_count }} films · {{ ucfirst($list->visibility) }}</span>
            </div>
        @empty
            <p class="empty-state">No lists.</p>
        @endforelse
    </div>

    <aside>
        <section class="panel" style="margin-top:0;">
            <h2>Moderation</h2>

            @if($user->isAdmin())
                <p class="panel-sub">Admin accounts can't be suspended, banned or deleted.</p>
            @else
                @if($state === 'suspended' || $state === 'banned')
                    <div class="notice" style="margin-bottom:14px;">
                        {{ $user->accessBlockedMessage() }}
                        @if($user->moderation_reason)<br><span class="text-muted">Reason: {{ $user->moderation_reason }}</span>@endif
                    </div>
                    <form method="POST" action="{{ route('admin.accounts.reinstate', $user) }}" style="margin-bottom:18px;">
                        @csrf
                        <button class="btn btn-primary btn-block">Reinstate account</button>
                    </form>
                @endif

                @unless($state === 'banned')
                    <form method="POST" action="{{ route('admin.accounts.suspend', $user) }}" class="panel-form" style="margin-bottom:18px;">
                        @csrf
                        <div class="field">
                            <label for="days">Suspend for</label>
                            <select id="days" name="days">
                                @foreach($suspensionOptions as $days => $label)
                                    <option value="{{ $days }}" @selected($days === 7)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="field">
                            <label for="suspendReason">Reason</label>
                            <input id="suspendReason" name="reason" type="text" maxlength="500" placeholder="e.g. Abusive review">
                        </div>
                        <button class="btn btn-secondary btn-block">{{ $state === 'suspended' ? 'Update suspension' : 'Suspend' }}</button>
                    </form>

                    <form method="POST" action="{{ route('admin.accounts.ban', $user) }}" class="panel-form" style="margin-bottom:18px;"
                          onsubmit="return confirm(@js('Permanently ban @'.$user->username.'?'))">
                        @csrf
                        <div class="field">
                            <label for="banReason">Ban reason</label>
                            <input id="banReason" name="reason" type="text" maxlength="500" placeholder="Repeated or severe violations">
                        </div>
                        <button class="btn btn-danger btn-block">Ban permanently</button>
                    </form>
                @endunless
            @endif

            <form method="POST" action="{{ route('admin.accounts.feature', $user) }}" style="margin-bottom:18px;">
                @csrf
                @method('PATCH')
                <button class="btn btn-secondary btn-block">{{ $user->is_featured ? 'Remove from Featured Members' : 'Feature this member' }}</button>
            </form>

            @unless($user->isAdmin())
                <form method="POST" action="{{ route('admin.accounts.destroy', $user) }}"
                      onsubmit="return confirm(@js('Delete @'.$user->username.' and all of their reviews, lists and activity? This cannot be undone.'))">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-ghost btn-block" style="color:var(--accent-red-bright);">Delete account…</button>
                </form>
            @endunless
        </section>
    </aside>
</div>
@endsection
