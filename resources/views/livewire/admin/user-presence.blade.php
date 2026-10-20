<span wire:poll.10s>
    @if($variant === 'avatar')
        <span class="presence-avatar {{ $member->isOnline() ? 'is-online' : '' }}">
            <img src="{{ $member->avatarUrl() }}" class="profile-avatar" alt="">
        </span>
    @else
        @include('partials.presence', ['member' => $member])
    @endif
</span>
