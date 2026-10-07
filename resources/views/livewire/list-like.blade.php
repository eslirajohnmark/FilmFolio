<button type="button" wire:click="toggle" class="vote-btn {{ $liked ? 'on disagree' : '' }}"
        title="{{ $liked ? 'Unlike list' : 'Like list' }}" aria-pressed="{{ $liked ? 'true' : 'false' }}">
    <span aria-hidden="true">{{ $liked ? '♥' : '♡' }}</span> {{ $liked ? 'Liked' : 'Like' }} <b>{{ number_format($count) }}</b>
</button>
