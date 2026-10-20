{{--
    $film     Film. Load it with Film::scopeWithCardStats() (views, hearts, average rating);
              otherwise each card runs its own count queries.
    $style    'tile' (rounded poster, default) or 'plate' (home page: purple card)
    $user     ?User   show this member's avatar on the poster
    $rating   ?float  that member's rating, shown with their avatar on the poster

    Under the poster: title and year, then the member's tappable stars with the view and heart counts.
--}}
@php
    $style = $style ?? 'tile';
    $plate = $style === 'plate';
    $member = auth()->user();
@endphp
<div class="poster-card {{ $style }}">
    <a href="{{ route('films.show', $film) }}" class="poster-link" title="{{ $film->title }} ({{ $film->year }})">
        <div class="poster" @if($film->posterUrl()) style="background-image:url('{{ $film->posterUrl() }}')" @endif>
            @unless($film->posterUrl())
                @include('partials.poster-placeholder', ['icon' => $plate ? 'film' : 'play'])
            @endunless

            @if(isset($user) || ! empty($rating))
                <div class="poster-meta {{ isset($user) ? 'always' : '' }}">
                    @isset($user)
                        <img src="{{ $user->avatarUrl() }}" alt="&#64;{{ $user->username ?? $user->name }}">
                    @endisset
                    @unless(empty($rating))
                        @include('partials.stars', ['rating' => $rating])
                    @endunless
                </div>
            @endif
        </div>
    </a>

    <div class="card-head">
        <a href="{{ route('films.show', $film) }}" class="card-title">{{ $film->title }}</a>
        <span class="card-year">{{ $film->year ?: 'TBA' }}</span>
    </div>

    @livewire('film-card-stats', [
        'film' => $film,
        'rating' => $member ? ($member->filmRatings()[$film->id] ?? 0) : 0,
        'average' => (float) (array_key_exists('reviews_avg_rating', $film->getAttributes())
            ? $film->reviews_avg_rating
            : $film->reviews()->avg('rating')),
        'views' => (int) ($film->watchers_count ?? $film->watchers()->count()),
        'hearts' => (int) ($film->likers_count ?? $film->likers()->count()),
    ], key('film-card-'.$film->id))
</div>
