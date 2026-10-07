@extends('layouts.app')

@section('title', 'Teasers · FilmFolio')

@section('content')
<div class="container teasers">
    <header class="page-hero-title">
        <h1 class="page-title">Teasers</h1>
        <p class="eyebrow">Upcoming films</p>
    </header>

    @if($featured)
        <div class="teaser-hero" x-data="{ playing: false }"
             @if($featured->backdropUrl()) style="--teaser-bg:url('{{ $featured->backdropUrl() }}')" @endif>
            <template x-if="playing">
                @include('partials.film-player', ['film' => $featured, 'prefer' => 'teaser', 'autoplay' => true])
            </template>

            <div class="teaser-hero-cover" x-show="! playing">
                @if($featured->hasTrailer())
                    <button type="button" class="teaser-play" @click="playing = true" aria-label="Play {{ $featured->title }} trailer">
                        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M8 5.5v13l11-6.5z"/></svg>
                    </button>
                @else
                    <span class="teaser-soon">Trailer coming soon</span>
                @endif

                <div class="teaser-hero-info">
                    <div class="teaser-hero-heading">
                        <h2><a href="{{ route('films.show', $featured) }}">{{ $featured->title }}</a></h2>
                        <span class="date-pill">{{ $featured->release_date ? strtoupper($featured->release_date->format('F j, Y')) : 'COMING SOON' }}</span>
                    </div>
                    @if($featured->synopsis)
                        <p>{{ $featured->synopsis }}</p>
                    @endif
                    <a href="{{ route('films.show', $featured) }}" class="teaser-more">View film →</a>
                </div>
            </div>
        </div>

        @if($upcoming->isNotEmpty())
            <div class="teaser-cards">
                @foreach($upcoming as $film)
                    <a href="{{ route('teasers.index', ['film' => $film->id]) }}" class="teaser-card" title="Play {{ $film->title }} trailer">
                        <span class="teaser-card-poster"
                              @if($film->posterUrl()) style="background-image:url('{{ $film->posterUrl() }}')" @endif></span>
                        <span class="teaser-card-title">{{ $film->title }}</span>
                        <span class="teaser-card-foot">
                            <span>{{ $film->release_date ? strtoupper($film->release_date->format('M j, Y')) : 'COMING SOON' }}</span>
                            <span class="mini-play" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M8 5.5v13l11-6.5z"/></svg>
                            </span>
                        </span>
                    </a>
                @endforeach
            </div>
        @endif
    @else
        <p class="empty-state text-center">
            No upcoming films yet.
            @if(auth()->user()?->isAdmin())
                Add teasers from the <a href="{{ route('admin.films.index') }}">admin Films page</a> or run <code>php artisan films:sync</code>.
            @endif
        </p>
    @endif
</div>
@endsection
