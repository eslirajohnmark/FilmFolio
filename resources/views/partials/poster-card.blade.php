{{--
    $film     Film (load reviews_avg_rating with withAvg('reviews', 'rating') for plate stars)
    $style    'tile' (rounded poster with title + year below, default) or
              'plate' (home page: purple card, "(YEAR) Title" and a row of stars)
    $user     ?User   show this member's avatar on the poster
    $rating   ?float  this rating instead of the film's average
--}}
@php
    $style = $style ?? 'tile';
    $plate = $style === 'plate';
@endphp
<a href="{{ route('films.show', $film) }}" class="poster-card {{ $style }}" title="{{ $film->title }} ({{ $film->year }})">
    <div class="poster" @if($film->posterUrl()) style="background-image:url('{{ $film->posterUrl() }}')" @endif>
        @unless($film->posterUrl())
            @include('partials.poster-placeholder', ['icon' => $plate ? 'film' : 'play'])
        @endunless

        @if(isset($user) || (! $plate && ! empty($rating)))
            <div class="poster-meta {{ isset($user) ? 'always' : '' }}">
                @isset($user)
                    <img src="{{ $user->avatarUrl() }}" alt="&#64;{{ $user->username ?? $user->name }}">
                @endisset
                @unless($plate)
                    @include('partials.stars', ['rating' => $rating ?? 0])
                @endunless
            </div>
        @endif
    </div>

    @if($plate)
        @php
            $stars = (float) ($rating ?? $film->reviews_avg_rating ?? 0);
            $label = $stars > 0 ? 'Rated '.number_format($stars, 1).' out of 5' : 'Not rated yet';
        @endphp
        <div class="caption">({{ $film->year ?: 'TBA' }}) {{ $film->title }}</div>
        <div class="plate-stars" title="{{ $label }}" aria-label="{{ $label }}">
            @for($i = 1; $i <= 5; $i++)
                <span class="{{ $stars >= $i ? 'on' : ($stars >= $i - 0.5 ? 'half' : '') }}" aria-hidden="true">★</span>
            @endfor
        </div>
    @else
        <div class="title">{{ $film->title }}</div>
        <div class="year">{{ $film->year ?: 'TBA' }}</div>
    @endif
</a>
