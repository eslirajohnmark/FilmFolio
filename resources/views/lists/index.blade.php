@extends('layouts.app')

@section('title', 'Lists · FilmFolio')

@section('content')
<div class="container lists-page">
    <div class="page-head" style="margin-bottom:8px;">
        <h1 class="page-title">Lists</h1>
    </div>
    <p class="page-lede">Your movie bucket list — collect, curate and share films.</p>

    @auth
        <form method="POST" action="{{ route('lists.store') }}" class="search-bar new-list-form">
            @csrf
            <input type="text" name="name" placeholder="Start a new list…" required aria-label="List name">
            <select name="visibility" aria-label="Visibility">
                <option value="public">Public</option>
                <option value="private">Private</option>
            </select>
            <button class="btn btn-primary">Create</button>
        </form>
        @error('name')
            <div class="error-list">{{ $message }}</div>
        @enderror
    @endauth

    @foreach([
        ['Featured Lists', $featured, 'Hand-picked by the FilmFolio crew.'],
        ['Recently Popular', $popular, 'Most liked this month.'],
        ['Crew Picks', $crewPicks, 'Official lists by genre and theme.'],
    ] as [$heading, $lists, $hint])
        @if($lists->isNotEmpty())
            <h2 class="section-heading">{{ $heading }} <small>{{ $hint }}</small></h2>
            <div class="card-grid">
                @foreach($lists as $list)
                    @include('partials.list-card', ['list' => $list])
                @endforeach
            </div>
        @endif
    @endforeach

    @auth
        <h2 class="section-heading">Your lists</h2>
        <div class="card-grid">
            @forelse($mine as $list)
                @include('partials.list-card', ['list' => $list])
            @empty
                <p class="empty-state">You haven't made any lists yet — start one above.</p>
            @endforelse
        </div>
    @endauth

    <h2 class="section-heading">Recently updated</h2>
    <div class="card-grid">
        @forelse($recent as $list)
            @include('partials.list-card', ['list' => $list])
        @empty
            <p class="empty-state">No public lists yet.</p>
        @endforelse
    </div>
    {{ $recent->links() }}
</div>
@endsection
