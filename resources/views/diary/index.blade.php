@extends('layouts.app')

@section('title', 'Diary · FilmFolio')

@section('content')
<div class="container">
    <h1 class="section-title">Diary</h1>
    <p class="section-subtitle">Every film you've logged, newest first.</p>

    <div class="stats-row">
        <div class="stat-box"><b>{{ $stats['total'] }}</b><span>Total</span></div>
        <div class="stat-box"><b>{{ $stats['this_year'] }}</b><span>This Year</span></div>
        <div class="stat-box"><b>{{ $stats['rewatches'] }}</b><span>Rewatches</span></div>
        <div class="stat-box"><b>{{ $stats['avg_rating'] }}</b><span>Avg Rating</span></div>
    </div>

    @forelse($entries as $entry)
        <div class="diary-page-row">
            <div class="diary-date">
                <div class="day">{{ $entry->watched_on->format('d') }}</div>
                <div class="month">{{ strtoupper($entry->watched_on->format('M')) }}</div>
            </div>

            @if($entry->film->posterUrl())
                <img src="{{ $entry->film->posterUrl() }}" class="diary-poster">
            @else
                <div class="diary-poster"></div>
            @endif

            <div class="diary-details">
                <a href="{{ route('films.show', $entry->film) }}" class="diary-title">
                    {{ $entry->film->title }} ({{ $entry->film->year }})
                </a>

                <div class="stars" style="color:var(--purple-glow);">
                    @php $r = (float) $entry->rating; @endphp
                    @if($r)
                        {{ str_repeat('★', (int) floor($r)) }}{{ ($r - floor($r)) >= 0.5 ? '½' : '' }}
                    @endif
                    @if($entry->rewatch)
                        <span class="badge" style="font-size:10px;margin-left:8px;">Rewatch</span>
                    @endif
                    @if($entry->liked)
                        <span style="color:var(--pink);margin-left:6px;">♥</span>
                    @endif
                </div>

                @if($entry->review)
                    <p style="color:var(--text-dim);font-size:13px;margin-top:8px;">
                        {{ Str::limit($entry->review, 240) }}
                    </p>
                @endif

                @if($entry->tags->isNotEmpty())
                    <div style="margin-top:8px;display:flex;gap:6px;flex-wrap:wrap;">
                        @foreach($entry->tags as $tag)
                            <a href="{{ route('tags.show', $tag) }}" class="tag-chip">{{ $tag->name }}</a>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    @empty
        <p style="color:var(--text-dim);padding:40px 0;">No diary entries yet.</p>
    @endforelse

    <div style="margin-top:24px;">{{ $entries->links() }}</div>
</div>
@endsection