@extends('layouts.app')

@section('content')
@if($user)
    {{-- Signed-in home (Letterboxd style) --}}
    <section class="hero">
        <div class="hero-inner welcome">
            <h1>Welcome back, <a href="{{ route('profile.show', $user) }}">{{ $user->name }}</a>.</h1>
            <p>Here's what your friends have been watching…</p>
        </div>
    </section>

    <div class="container home-sections">
        <div class="section-label">New from friends <a href="{{ route('feed.index') }}">Activity →</a></div>
        @if($friendsWatching->isEmpty())
            <p class="empty-state">
                Nothing from friends yet. <a href="{{ route('members.index') }}">Find members to follow →</a>
            </p>
        @else
            <div class="poster-row">
                @foreach($friendsWatching as $entry)
                    @include('partials.poster-card', ['film' => $entry->film, 'user' => $entry->user, 'rating' => $entry->rating, 'style' => 'plate'])
                @endforeach
            </div>
        @endif

        @include('partials.suggestions', ['style' => 'plate'])

        <div class="section-label">Popular films this week <a href="{{ route('films.index') }}">More →</a></div>
        <div class="poster-row">
            @forelse($popularFilms as $film)
                @include('partials.poster-card', ['film' => $film, 'style' => 'plate'])
            @empty
                <p class="empty-state">No films yet.</p>
            @endforelse
        </div>

        @livewire('review-feed', ['limit' => 4])

        <div class="section-label">Recent activity</div>
        @forelse($feed as $activity)
            @include('partials.activity-row', ['activity' => $activity])
        @empty
            <p class="empty-state">
                Follow members to fill your feed. <a href="{{ route('members.index') }}">Browse members →</a>
            </p>
        @endforelse
    </div>
@else
    {{-- Guest landing page (mockup) --}}
    <section class="hero landing-hero">
        <div class="hero-inner">
            <h1>
                Track films you’ve watched.<br>
                Save those you want to see.<br>
                Tell your friends what’s good.
            </h1>
            <div class="landing-cta">
                <a href="{{ route('register') }}" class="btn-cta">Get Started - it's free!</a>
            </div>
            <p class="tagline">The social network for film lovers.</p>
        </div>
    </section>

    <div class="container home-sections">
        @include('partials.suggestions', ['style' => 'plate'])

        <div class="section-label">Just reviewed...</div>
        <div class="poster-row">
            @forelse($justReviewed as $film)
                @include('partials.poster-card', ['film' => $film, 'style' => 'plate'])
            @empty
                <p class="empty-state">No films yet.</p>
            @endforelse
        </div>

        @livewire('review-feed', ['limit' => 4])
    </div>
@endif
@endsection
