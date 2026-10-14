@extends('layouts.app')

@section('title', 'Films · FilmFolio')

@section('content')
<div class="container">
    <div class="page-head bordered">
        <h1 class="page-title-sans">Films</h1>
        <a href="{{ auth()->check() ? route('search') : route('login') }}" class="btn-primary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.85 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/></svg>
            Write a Review
        </a>
    </div>

    @include('partials.suggestions')

    @livewire('film-search')

    @if($popular->isNotEmpty())
        <div class="section-label">Popular this week <a href="{{ route('teasers.index') }}">Upcoming teasers →</a></div>
        <div class="poster-row">
            @foreach($popular as $film)
                @include('partials.poster-card', ['film' => $film])
            @endforeach
        </div>
    @endif

    @livewire('review-feed')
</div>
@endsection
