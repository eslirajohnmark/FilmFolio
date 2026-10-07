{{--
    $review     Review (with user, film; optional comments_count)
    $showFilm   bool   poster + film title (default true); false shows the author avatar instead
--}}
@php $showFilm = $showFilm ?? true; $film = $review->film; $author = $review->user; @endphp
<article class="review-card">
    @if($showFilm)
        <a href="{{ route('films.show', $film) }}" class="poster"
           @if($film->posterUrl()) style="background-image:url('{{ $film->posterUrl() }}')" @endif>@unless($film->posterUrl())@include('partials.poster-placeholder', ['icon' => 'film'])@endunless</a>
    @else
        <a href="{{ route('profile.show', $author) }}">
            <img src="{{ $author->avatarUrl() }}" class="activity-avatar" alt="">
        </a>
    @endif

    <div class="review-body">
        @if($showFilm)
            <h4 class="review-title">
                <a href="{{ route('reviews.show', $review) }}">{{ $film->title }}</a>
                <span class="year">({{ $film->year ?: 'TBA' }})</span>
            </h4>
        @endif

        @if($review->body)
            <a href="{{ route('reviews.show', $review) }}"><p class="review-text">{{ $review->body }}</p></a>
        @endif

        <div class="review-footer">
            <a href="{{ route('profile.show', $author) }}" class="review-author">-{{ $author->username ?? $author->name }}</a>
            @include('partials.stars', ['rating' => $review->rating])
        </div>
        <div class="review-footer" style="margin-top:6px;">
            @livewire('review-vote', ['review' => $review], key('vote-'.$review->id.'-'.($showFilm ? 'f' : 'a')))
            @isset($review->comments_count)
                <a href="{{ route('reviews.show', $review) }}#comments">💬 {{ $review->comments_count }}</a>
            @endisset
            @include('partials.review-owner-actions', ['review' => $review])
        </div>
    </div>
</article>
