@extends('layouts.app')

@section('title', $user->name.' (@'.($user->username ?? $user->name).') · FilmFolio')

@section('content')
@php
    $tabs = [
        'profile' => 'Profile', 'activity' => 'Activity', 'films' => 'Films', 'diary' => 'Diary',
        'reviews' => 'Reviews', 'ratings' => 'Ratings', 'watchlist' => 'Watchlist', 'lists' => 'Lists', 'likes' => 'Likes',
    ];
@endphp
<div class="container">
    @include('partials.profile-header', ['user' => $user])

    <nav class="tabs">
        @foreach($tabs as $key => $label)
            <a href="{{ route('profile.show', $key === 'profile' ? $user : [$user, 'tab' => $key]) }}"
               class="{{ $tab === $key ? 'active' : '' }}">{{ $label }}</a>
        @endforeach
    </nav>

    @if($tab === 'profile')
        <div class="section-label">Favorite films</div>
        @if($favorites->isEmpty())
            <p class="empty-state">
                No favourites yet.
                @if($isOwner) Use “☆ Add to favourites” on any film page to pick up to four. @endif
            </p>
        @else
            <div class="favorites">
                @foreach($favorites as $film)
                    @include('partials.poster-card', ['film' => $film])
                @endforeach
            </div>
        @endif

        <div class="section-label">Recent activity <a href="{{ route('profile.show', [$user, 'tab' => 'diary']) }}">Diary →</a></div>
        @if($recentDiary->isEmpty())
            <p class="empty-state">Nothing logged yet.</p>
        @else
            <div class="poster-row">
                @foreach($recentDiary as $entry)
                    @include('partials.poster-card', ['film' => $entry->film, 'rating' => $entry->rating])
                @endforeach
            </div>
        @endif

        <div class="section-label">Recent reviews <a href="{{ route('profile.show', [$user, 'tab' => 'reviews']) }}">All →</a></div>
        <div class="review-grid">
            @forelse($recentReviews as $review)
                @include('partials.review-card', ['review' => $review])
            @empty
                <p class="empty-state">No reviews yet.</p>
            @endforelse
        </div>

    @elseif(in_array($tab, ['films', 'watchlist', 'likes'], true))
        <div class="poster-grid" style="margin-top:24px;">
            @forelse($items as $film)
                @include('partials.poster-card', ['film' => $film])
            @empty
                <p class="empty-state">Nothing here yet.</p>
            @endforelse
        </div>

    @elseif($tab === 'activity')
        <div style="margin-top:12px;">
            @forelse($items as $activity)
                @include('partials.activity-row', ['activity' => $activity])
            @empty
                <p class="empty-state">No activity yet.</p>
            @endforelse
        </div>

    @elseif($tab === 'ratings')
        <div class="poster-grid" style="margin-top:24px;">
            @forelse($items as $review)
                <div>
                    @include('partials.poster-card', ['film' => $review->film, 'rating' => $review->rating])
                    <div style="display:flex;justify-content:center;align-items:center;gap:8px;margin-top:6px;">
                        @include('partials.stars', ['rating' => $review->rating])
                        @if($isOwner)
                            @include('partials.review-owner-actions', ['review' => $review])
                        @endif
                    </div>
                </div>
            @empty
                <p class="empty-state">No ratings yet.</p>
            @endforelse
        </div>

    @elseif($tab === 'reviews')
        <div class="review-grid" style="margin-top:24px;">
            @forelse($items as $review)
                @include('partials.review-card', ['review' => $review])
            @empty
                <p class="empty-state">No reviews yet.</p>
            @endforelse
        </div>

    @elseif($tab === 'lists')
        <div class="card-grid" style="margin-top:24px;">
            @forelse($items as $list)
                @include('partials.list-card', ['list' => $list])
            @empty
                <p class="empty-state">No lists yet.</p>
            @endforelse
        </div>

    @else
        <div style="margin-top:12px;">
            @forelse($items as $entry)
                <div class="diary-page-row">
                    <div class="diary-date">
                        <div class="day">{{ $entry->watched_on->format('d') }}</div>
                        <div class="month">{{ strtoupper($entry->watched_on->format('M Y')) }}</div>
                    </div>
                    <a href="{{ route('films.show', $entry->film) }}">
                        @if($entry->film->posterUrl())
                            <img src="{{ $entry->film->posterUrl() }}" class="diary-poster" alt="">
                        @else
                            <div class="diary-poster"></div>
                        @endif
                    </a>
                    <div>
                        <a href="{{ route('films.show', $entry->film) }}" class="diary-title">{{ $entry->film->title }}</a>
                        <span class="review-title"><span class="year">({{ $entry->film->year }})</span></span>
                        <div style="margin-top:4px;">
                            @include('partials.stars', ['rating' => $entry->rating])
                            @if($entry->rewatch) <span class="badge" style="font-size:10px;">Rewatch</span> @endif
                            @if($entry->liked) <span style="color:var(--accent-red-bright);">♥</span> @endif
                        </div>
                        @if($entry->review)
                            <p class="activity-snippet">{{ Str::limit($entry->review, 240) }}</p>
                        @endif
                    </div>
                </div>
            @empty
                <p class="empty-state">Nothing logged yet.</p>
            @endforelse
        </div>
    @endif

    @isset($items)
        {{ $items->links() }}
    @endisset
</div>
@endsection
