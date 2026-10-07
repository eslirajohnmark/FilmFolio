@extends('layouts.app')

@section('title', $film->title.' ('.$film->year.') · FilmFolio')

@section('content')
<div class="film-backdrop {{ $film->backdropUrl() ? '' : 'empty' }}"
     @if($film->backdropUrl()) style="background-image:url('{{ $film->backdropUrl() }}')" @endif></div>

<div class="container film-page">
    <div class="film-hero">
        {{-- Poster --}}
        <div class="film-poster-col">
            @if($film->posterUrl())
                <img src="{{ $film->posterUrl() }}" alt="{{ $film->title }}" class="film-poster">
            @else
                <div class="poster film-poster" style="font-size:60px;">🎞</div>
            @endif

            <div class="film-stats">
                <span title="Watched by"><b>{{ number_format($film->watchers_count) }}</b> 👁</span>
                <span title="Liked by"><b>{{ number_format($film->likers_count) }}</b> ♥</span>
                <span title="In lists"><b>{{ number_format($film->lists_count) }}</b> ☰</span>
            </div>
        </div>

        {{-- Details --}}
        <div>
            <h1 class="film-title">{{ $film->title }}<span class="year">{{ $film->year }}</span></h1>
            <div class="film-meta">
                @if($film->genre)<span>{{ strtoupper($film->genre) }}</span>@endif
                @if($film->release_date)<span>RELEASED {{ $film->release_date->format('M d, Y') }}</span>@endif
                @if($film->hasTrailer())
                    <a href="#trailer" class="link">▶ TRAILER</a>
                @endif
            </div>

            @if($film->synopsis)
                <p class="film-synopsis">{{ $film->synopsis }}</p>
            @endif

            @if($film->tags->isNotEmpty())
                <div style="display:flex;gap:6px;flex-wrap:wrap;">
                    @foreach($film->tags as $tag)
                        <a href="{{ route('tags.show', $tag) }}" class="tag-chip">#{{ $tag->name }}</a>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Actions + ratings --}}
        <aside class="film-sidebar">
            @livewire('film-actions', ['film' => $film], key('fa-'.$film->id))

            @auth
                <div style="margin-top:10px;">
                    @livewire('add-to-list', ['film' => $film], key('atl-'.$film->id))
                </div>
            @endauth

            <div class="ratings-box">
                <div class="head">
                    <span>Ratings</span>
                    <span>{{ number_format($film->reviews_count) }} {{ Str::plural('fan', $film->reviews_count) }}</span>
                </div>
                <div style="display:flex;align-items:flex-end;gap:12px;">
                    @php $max = max(1, $histogram->max()); @endphp
                    <div class="histogram" style="flex:1;">
                        @foreach($histogram as $stars => $total)
                            <div class="bar" style="height:{{ max(3, round($total / $max * 100)) }}%;"
                                 title="{{ $total }} × {{ $stars }}★"></div>
                        @endforeach
                    </div>
                    <div class="avg">{{ $film->reviews_avg_rating ? number_format($film->reviews_avg_rating, 1) : '–' }}</div>
                </div>
                <div style="display:flex;justify-content:space-between;font-size:10px;color:var(--text-muted);margin-top:2px;">
                    <span class="stars" style="font-size:10px;">★</span>
                    <span class="stars" style="font-size:10px;">★★★★★</span>
                </div>
            </div>
        </aside>
    </div>

    {{-- Trailer (uploaded video or YouTube) --}}
    @if($film->hasTrailer())
        <div id="trailer" class="section-label">Trailer</div>
        <div style="max-width:860px;">
            @include('partials.film-player', ['film' => $film, 'prefer' => 'trailer'])
        </div>
    @endif

    {{-- Diary --}}
    <div id="diary" class="section-label">Log a watch</div>
    @livewire('diary-logger', ['film' => $film], key('dl-'.$film->id))

    {{-- Your review --}}
    <div id="your-review" class="section-label">Your review</div>
    @auth
        <form method="POST" action="{{ route('films.review', $film) }}" class="panel panel-form">
            @csrf

            @if($errors->any())
                <div class="error-list">
                    @foreach($errors->all() as $error) <div>{{ $error }}</div> @endforeach
                </div>
            @endif

            @php $current = (float) old('rating', $myReview->rating ?? 0); @endphp
            <div class="field">
                <label>Rating</label>
                <div class="star-rating" id="reviewStars">
                    @for($i = 1; $i <= 5; $i++)
                        <span class="star-wrap">
                            <span class="star-bg" onclick="setRating({{ $i }})">
                                <span class="star-fill {{ $current >= $i ? 'full' : ($current >= $i - 0.5 ? 'half' : '') }}">★</span>★
                            </span>
                            <span class="star-half-btn" onclick="setRating({{ $i - 0.5 }})">½</span>
                        </span>
                    @endfor
                    <input type="hidden" name="rating" id="ratingInput" value="{{ $current ?: '' }}">
                    <span class="rating-value" id="ratingDisplay">{{ $current ?: '—' }}</span>
                </div>
            </div>

            <div class="field">
                <label for="reviewBody">Review</label>
                <textarea id="reviewBody" name="body" placeholder="Add a review…">{{ old('body', $myReview->body ?? '') }}</textarea>
            </div>

            <div class="actions">
                <button type="submit" class="btn btn-primary">{{ $myReview?->body ? 'Update Review' : 'Post Review' }}</button>
                @if($myReview)
                    <a href="{{ route('reviews.show', $myReview) }}" class="link" style="font-size:13px;">View your review →</a>
                @endif
            </div>
        </form>
    @else
        <p class="empty-state"><a href="{{ route('login') }}">Log in</a> to rate, log and review this film.</p>
    @endauth

    {{-- Popular reviews --}}
    <div class="section-label">Popular reviews</div>
    <div class="review-grid">
        @forelse($reviews as $review)
            @include('partials.review-card', ['review' => $review, 'showFilm' => false])
        @empty
            <p class="empty-state">No reviews yet. Be the first!</p>
        @endforelse
    </div>
</div>

<script>
function setRating(value) {
    document.getElementById('ratingInput').value = value;
    document.getElementById('ratingDisplay').textContent = value;

    document.querySelectorAll('#reviewStars .star-wrap').forEach((wrap, index) => {
        const fill = wrap.querySelector('.star-fill');
        const i = index + 1;
        fill.classList.remove('full', 'half');
        if (value >= i) fill.classList.add('full');
        else if (value >= i - 0.5) fill.classList.add('half');
    });
}
</script>
@endsection
