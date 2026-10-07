@extends('layouts.app')

@section('title', 'Journal · FilmFolio')

@section('content')
<div class="container journal">
    <div class="page-head">
        <h1 class="page-title">Journal</h1>
        <a href="{{ auth()->check() ? route('search') : route('login') }}" class="btn-primary small">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
            Log a Film
        </a>
    </div>

    @forelse($months as $month => $entries)
        <section class="journal-month">
            <h2 class="journal-month-title">{{ $month }}</h2>

            @foreach($entries->chunk(2) as $pair)
                <div class="journal-row">
                    @foreach($pair as $review)
                        <a href="{{ route('reviews.show', $review) }}" class="journal-entry">
                            <span class="journal-thumb"
                                  @if($review->film->posterUrl()) style="background-image:url('{{ $review->film->posterUrl() }}')" @endif></span>
                            <span class="journal-body">
                                <span class="journal-head">
                                    <b>{{ $review->film->title }} ({{ $review->film->year ?: 'TBA' }})</b>
                                    <span class="journal-date">{{ $review->created_at->format('j M') }}</span>
                                    @include('partials.stars', ['rating' => $review->rating])
                                </span>
                                <span class="journal-text">{{ $review->body }}</span>
                                <span class="journal-author">&#64;{{ $review->user->username ?? $review->user->name }}</span>
                            </span>
                        </a>
                    @endforeach
                </div>
            @endforeach
        </section>
    @empty
        <p class="empty-state">No reviews yet. Open any film and write the first one.</p>
    @endforelse

    {{ $reviews->links() }}
</div>
@endsection
