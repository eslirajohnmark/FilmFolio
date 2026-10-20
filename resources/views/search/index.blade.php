@extends('layouts.app')

@section('title', ($q ? 'Search: '.$q : 'Search').' · FilmFolio')

@section('content')
<div class="container">
    <form action="{{ route('search') }}" method="GET" class="search-bar" role="search">
        <input type="search" name="q" value="{{ $q }}" placeholder="Search for a film, member or list…" autofocus>
        <button class="btn btn-primary">Search</button>
    </form>

    @include('partials.suggestions')

    @if(mb_strlen($q) < 2)
        <p class="empty-state">Search for a film to open its page, then rate it, review it or log a watch. Members and lists show up here too.</p>
    @else
        <div class="section-label">Films matching “{{ $q }}”</div>
        @if($films->isEmpty() && $tmdbResults->isEmpty())
            <p class="empty-state">No films found.</p>
        @endif

        <div class="poster-grid">
            @foreach($films as $film)
                @include('partials.poster-card', ['film' => $film])
            @endforeach

            @foreach($tmdbResults as $movie)
                <a href="{{ route('films.tmdb', $movie['id']) }}" class="poster-card" title="Add {{ $movie['title'] }} from TMDB">
                    <div class="poster" @if($movie['poster']) style="background-image:url('{{ $movie['poster'] }}')" @endif>
                        @unless($movie['poster'])@include('partials.poster-placeholder')@endunless
                    </div>
                    <div class="card-head">
                        <span class="card-title">{{ $movie['title'] }}</span>
                        <span class="card-year">{{ $movie['year'] ?? 'TBA' }}</span>
                    </div>
                    <div class="card-stats">
                        <span class="card-chip">+ Add from TMDB</span>
                    </div>
                </a>
            @endforeach
        </div>

        @if($members->isNotEmpty())
            <div class="section-label">Members</div>
            <div class="card-grid">
                @foreach($members as $member)
                    <a href="{{ route('profile.show', $member) }}" class="member-card">
                        <img src="{{ $member->avatarUrl() }}" class="member-avatar" alt="">
                        <div class="member-info">
                            <div class="name">&#64;{{ $member->username ?? $member->name }}</div>
                            <div class="stats">{{ $member->name }}</div>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif

        @if($lists->isNotEmpty())
            <div class="section-label">Lists</div>
            <div class="card-grid">
                @foreach($lists as $list)
                    @include('partials.list-card', ['list' => $list])
                @endforeach
            </div>
        @endif
    @endif
</div>
@endsection
