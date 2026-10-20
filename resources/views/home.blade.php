@extends('layouts.app')

{{--
    Home, in FilmFolio's blue / violet / red. Each visit opens with the intro (partials/intro), after
    which the headline letters fly in from all over the screen: text marked data-scatter is split
    into letters in the browser, and data-scatter-colors gives each line its gradient.
--}}
@section('content')
<div class="home-page">
@if($user)
    {{-- Signed-in home --}}
    <section class="hero home-hero">
        <div class="landing-glow" aria-hidden="true">
            <span class="glow-blue"></span><span class="glow-violet"></span><span class="glow-red"></span>
        </div>
        <div class="hero-inner welcome">
            <h1 class="welcome-back" data-scatter data-scatter-colors="#93c5fd,#a78bfa,#f87171">Welcome back, <a href="{{ route('profile.show', $user) }}" class="welcome-name">{{ $user->name }}</a>!</h1>
            <p class="hero-reveal" style="--i:0">Here's what your friends have been watching…</p>
            <div class="home-actions hero-reveal" style="--i:1">
                <a href="{{ route('search') }}" class="home-action blue">Find a film</a>
                <a href="{{ route('diary.index') }}" class="home-action violet">My diary</a>
                <a href="{{ route('watchlist.index') }}" class="home-action red">Watchlist</a>
            </div>
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
    {{-- Guest landing page --}}
    <section class="hero home-hero landing-hero">
        <div class="landing-glow" aria-hidden="true">
            <span class="glow-blue"></span><span class="glow-violet"></span><span class="glow-red"></span>
        </div>
        <div class="hero-inner">
            <p class="landing-eyebrow hero-reveal" style="--i:0"><i aria-hidden="true"></i>Your life in film</p>
            <h1>
                <span class="landing-line line-blue" data-scatter data-scatter-colors="#bfdbfe,#60a5fa,#3b82f6">Track films you’ve watched.</span>
                <span class="landing-line line-violet" data-scatter data-scatter-colors="#ddd6fe,#a78bfa,#8b5cf6">Save those you want to see.</span>
                <span class="landing-line line-red" data-scatter data-scatter-colors="#fecaca,#f87171,#ef4444">Tell your friends what’s good.</span>
            </h1>
            <div class="landing-cta hero-reveal" style="--i:1">
                <a href="{{ route('register') }}" class="btn-cta btn-trio">Get Started - it's free!</a>
                <a href="{{ route('films.index') }}" class="btn-ghost-trio">Browse films →</a>
            </div>
            <p class="tagline hero-reveal" style="--i:2">The social network for film lovers.</p>
        </div>
    </section>

    <div class="container home-sections">
        {{-- How it works: one card per colour --}}
        <div class="home-features">
            <a href="{{ route('films.index') }}" class="home-feature blue">
                <span class="home-feature-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><rect x="3.5" y="5" width="17" height="14" rx="2"/><path d="M7.5 5v14M16.5 5v14M3.5 9.7h4M3.5 14.3h4M16.5 9.7h4M16.5 14.3h4"/></svg>
                </span>
                <b>Log every watch</b>
                <span>Keep a diary of every film you see, with a rating and a few words.</span>
            </a>
            <a href="{{ route('teasers.index') }}" class="home-feature violet">
                <span class="home-feature-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="8.5"/><path d="M12 7.5V12l3 2.5"/></svg>
                </span>
                <b>Build your watchlist</b>
                <span>Save what you want to see next, from new releases to old classics.</span>
            </a>
            <a href="{{ route('lists.index') }}" class="home-feature red">
                <span class="home-feature-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1-1.1a5.5 5.5 0 0 0-7.8 7.8l1 1.1L12 21l7.8-7.5 1-1.1a5.5 5.5 0 0 0 0-7.8z"/></svg>
                </span>
                <b>Share your reviews</b>
                <span>Tell your friends what's good, make lists and follow their picks.</span>
            </a>
        </div>

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
</div>
@endsection
