{{-- Re-renders every 10 seconds so the online dots and new members stay current --}}
<div wire:poll.10s>
    <div class="admin-toolbar">
        <div class="search-bar" style="margin:0;flex:1;max-width:420px;">
            <input type="search" placeholder="Search by name, username or email…" wire:model.live.debounce.300ms="q" aria-label="Search members">
        </div>
        <div class="filter-tabs">
            @foreach($statuses as $key => $label)
                <button type="button" wire:click="$set('status', '{{ $key }}')" class="{{ $status === $key ? 'active' : '' }}">{{ $label }}</button>
            @endforeach
            @if($joinedLabel)
                <button type="button" wire:click="$set('joined', '')" class="active" title="Clear this filter">{{ $joinedLabel }} ✕</button>
            @endif
        </div>
    </div>

    <div class="table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th></th>
                    <th>Member</th>
                    <th>Email</th>
                    <th>Joined</th>
                    <th style="text-align:right;">Reviews</th>
                    <th style="text-align:right;">Logs</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                    @php $state = $user->accountStatus(); @endphp
                    <tr wire:key="u-{{ $user->id }}">
                        <td style="width:46px;">
                            <span class="presence-avatar small {{ $user->isOnline() ? 'is-online' : '' }}">
                                <img src="{{ $user->avatarUrl() }}" class="member-avatar" style="width:34px;height:34px;" alt="">
                            </span>
                        </td>
                        <td>
                            <a href="{{ route('admin.accounts.show', $user) }}" style="color:#fff;font-weight:600;">&#64;{{ $user->username ?? $user->name }}</a>
                            <div class="text-muted" style="font-size:12px;">{{ $user->name }}</div>
                            @include('partials.presence', ['member' => $user])
                        </td>
                        <td>{{ $user->email }}</td>
                        <td>{{ $user->created_at->format('M d, Y') }}</td>
                        <td style="text-align:right;">{{ $user->reviews_count }}</td>
                        <td style="text-align:right;">{{ $user->diary_entries_count }}</td>
                        <td>
                            <span class="status status-{{ $state === 'active' ? 'active' : ($state === 'banned' ? 'banned' : 'suspended') }}">
                                {{ ucfirst($state) }}
                            </span>
                            @if($user->is_featured) <span class="badge" style="font-size:9px;">Featured</span> @endif
                        </td>
                        <td><a href="{{ route('admin.accounts.show', $user) }}" class="btn btn-secondary" style="padding:4px 10px;font-size:12px;">Manage</a></td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="empty-state">No members found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $users->links() }}
</div>
