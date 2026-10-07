<div class="star-rating {{ $readonly ? 'readonly' : '' }}">
    @for($i = 1; $i <= 5; $i++)
        @php
            $full = $rating >= $i;
            $half = ! $full && $rating >= $i - 0.5;
        @endphp
        <span class="star-wrap">
            <span class="star-bg"
                  wire:click="setRating({{ $i }})"
                  style="cursor:{{ $readonly ? 'default' : 'pointer' }};">
                <span class="star-fill {{ $full ? 'full' : ($half ? 'half' : '') }}">★</span>★
            </span>
            @unless($readonly)
                <span class="star-half-btn"
                      wire:click="setRating({{ $i - 0.5 }})"
                      title="{{ $i - 0.5 }}">½</span>
            @endunless
        </span>
    @endfor

    <span class="rating-value">{{ $rating ?: '—' }}</span>

    @unless($readonly)
        @if($rating > 0)
            <button wire:click="clear" class="rating-clear">clear</button>
        @endif
    @endunless
</div>
