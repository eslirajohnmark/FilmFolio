@extends('layouts.app')

@section('content')
<div class="container" style="padding-top:60px;">
    <h1 class="section-title">#{{ $tag->name }}</h1>
    <p class="section-subtitle">{{ $tag->use_count }} uses · {{ $films->total() }} films</p>

    <div class="poster-grid">
        @foreach($films as $film)
            @include('partials.poster-card', ['film' => $film])
        @endforeach
    </div>

    @if($films->isEmpty())
        <p style="color:var(--text-dim);">No films tagged yet.</p>
    @endif

    <div style="margin-top:24px;">{{ $films->links() }}</div>

    @if($diaries->isNotEmpty())
        <h2 class="section-title small" style="margin-top:40px;">Recent Diary Entries</h2>
        @foreach($diaries as $entry)
            <div class="activity-row">
                <img src="{{ $entry->user->avatarUrl() }}" class="activity-avatar" alt="">
                <div class="activity-content">
                    <a href="{{ route('profile.show', $entry->user) }}" class="activity-user">
                        &#64;{{ $entry->user->username ?? $entry->user->name }}
                    </a>
                    watched
                    <a href="{{ route('films.show', $entry->film) }}">{{ $entry->film->title }}</a>
                    @if($entry->review)
                        <div class="activity-snippet">"{{ Str::limit($entry->review, 140) }}"</div>
                    @endif
                    <div class="activity-time">{{ $entry->watched_on->format('M d, Y') }}</div>
                </div>
            </div>
        @endforeach
    @endif
</div>
@endsection