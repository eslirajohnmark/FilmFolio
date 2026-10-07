<div>
    <div class="page-head members-head">
        <h1 class="page-title">Members</h1>
        <label class="pill-search">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
            <input type="search" placeholder="Search for users…" aria-label="Search for users"
                   wire:model.live.debounce.300ms="q">
        </label>
    </div>

    <h2 class="section-heading">{{ $q ? "Members matching “{$q}”" : 'Most Active Reviewers' }}</h2>

    <div class="member-grid" wire:loading.class="is-loading">
        @forelse($users as $user)
            @include('partials.member-card', ['user' => $user, 'followingIds' => $followingIds, 'keyPrefix' => 'search'])
        @empty
            <p class="empty-state">No members match “{{ $q }}”.</p>
        @endforelse
    </div>

    {{ $users->links() }}
</div>
