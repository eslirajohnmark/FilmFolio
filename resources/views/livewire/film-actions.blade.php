<div class="film-actions">
    <div class="action-row">
        <button type="button" wire:click="toggleWatched" class="action {{ $watched ? 'on watched' : '' }}" title="{{ $watched ? 'Remove from watched' : 'Mark as watched' }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"/><circle cx="12" cy="12" r="3"/></svg>
            {{ $watched ? 'Watched' : 'Watch' }}
        </button>
        <button type="button" wire:click="toggleLiked" class="action {{ $liked ? 'on liked' : '' }}" title="{{ $liked ? 'Unlike' : 'Like' }}">
            <svg viewBox="0 0 24 24" fill="{{ $liked ? 'currentColor' : 'none' }}" stroke="currentColor" stroke-width="2"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1-1.1a5.5 5.5 0 0 0-7.8 7.8l1 1.1L12 21l7.8-7.5 1-1.1a5.5 5.5 0 0 0 0-7.8z"/></svg>
            {{ $liked ? 'Liked' : 'Like' }}
        </button>
        <button type="button" wire:click="toggleWatchlist" class="action {{ $inWatchlist ? 'on queued' : '' }}" title="{{ $inWatchlist ? 'Remove from watchlist' : 'Add to watchlist' }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/></svg>
            Watchlist
        </button>
    </div>

    <div class="rate">
        <div class="rate-label">{{ $rating ? 'Rated' : 'Rate' }}</div>
        <div class="quick-stars" role="radiogroup" aria-label="Your rating">
            @for($half = 1; $half <= 10; $half++)
                @php $value = $half / 2; @endphp
                <button type="button"
                        wire:click="rate({{ $value }})"
                        class="{{ $half % 2 === 0 ? 'right' : '' }} {{ $rating >= $value ? 'on' : '' }}"
                        title="{{ $value }} stars" aria-label="{{ $value }} stars"><span>★</span></button>
            @endfor
        </div>
        @if($rating)
            <div><button type="button" wire:click="clearRating" class="rating-clear">Remove rating</button></div>
        @endif
    </div>

    <a href="#diary" class="menu-item">Review or log…</a>
    <button type="button" wire:click="toggleFavorite" class="menu-item">
        {{ $isFavorite ? '★ Remove from favourites' : '☆ Add to favourites' }}
    </button>
</div>
