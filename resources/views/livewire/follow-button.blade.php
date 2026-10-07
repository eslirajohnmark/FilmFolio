<button wire:click="toggle"
        class="btn-follow {{ $following ? 'following' : '' }}"
        @if(auth()->id() === $user->id) disabled @endif>
    @if(auth()->id() === $user->id)
        You
    @elseif($following)
        ✓ Following
    @else
        Follow
    @endif
    <span class="follow-count">{{ $followersCount }}</span>
</button>
