@php
    // Your own rating once you've rated the film; until then the members' average, dimmed.
    $shown = $rating ?: round($average * 2) / 2;
    $score = $rating ?: $average;
    $label = $rating
        ? "Your rating: {$rating} stars. Tap to change it."
        : ($average > 0 ? 'Average '.number_format($average, 1).' stars. Tap to rate.' : 'Not rated yet. Tap to rate.');
@endphp
<div class="card-stats">
    <div class="card-rating">
        <div class="card-stars {{ $rating ? 'mine' : 'average' }}" role="group" aria-label="{{ $label }}" title="{{ $label }}">
            @for($half = 1; $half <= 10; $half++)
                @php $value = $half / 2; @endphp
                <button type="button"
                        wire:click="rate({{ $value }})"
                        class="{{ $half % 2 === 0 ? 'right' : '' }} {{ $shown >= $value ? 'on' : '' }}"
                        aria-label="Rate {{ $value }} {{ $value == 1 ? 'star' : 'stars' }}"><span aria-hidden="true">★</span></button>
            @endfor
        </div>
        @if($score > 0)
            <span class="card-score {{ $rating ? 'mine' : '' }}" aria-hidden="true">{{ number_format($score, 1) }}</span>
        @endif
    </div>

    <div class="card-counts">
        <span class="card-chip" title="Watched by {{ number_format($views) }} {{ $views === 1 ? 'member' : 'members' }}">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M1.5 12S5.5 5 12 5s10.5 7 10.5 7-4 7-10.5 7S1.5 12 1.5 12z"/><circle cx="12" cy="12" r="3"/></svg>
            <span class="sr-only">Views</span>{{ number_format($views) }}
        </span>
        <span class="card-chip hearts {{ $hearts > 0 ? 'loved' : '' }}" title="Liked by {{ number_format($hearts) }} {{ $hearts === 1 ? 'member' : 'members' }}">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1-1.1a5.5 5.5 0 0 0-7.8 7.8l1 1.1L12 21l7.8-7.5 1-1.1a5.5 5.5 0 0 0 0-7.8z"/></svg>
            <span class="sr-only">Hearts</span>{{ number_format($hearts) }}
        </span>
    </div>
</div>
