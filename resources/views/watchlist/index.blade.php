@extends('layouts.app')

@section('title', 'Watchlist · FilmFolio')

@section('content')
<div class="container">
    <h1 class="section-title">Watchlist</h1>
    <p class="section-subtitle">{{ $films->total() }} {{ Str::plural('film', $films->total()) }} you want to see. Watching a film takes it off this list.</p>

    <div class="poster-grid">
        @forelse($films as $film)
            <div>
                @include('partials.poster-card', ['film' => $film])
                <div style="margin-top:6px;text-align:center;">
                    @livewire('watchlist-toggle', ['film' => $film], key('wl-'.$film->id))
                </div>
            </div>
        @empty
            <p class="empty-state">Nothing here yet. <a href="{{ route('films.index') }}">Browse films →</a></p>
        @endforelse
    </div>

    {{ $films->links() }}
</div>
@endsection
