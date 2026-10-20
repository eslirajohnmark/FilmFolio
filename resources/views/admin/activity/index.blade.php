@extends('layouts.admin')

@section('title', 'Activity')

@section('content')
@use('App\Http\Controllers\Admin\ActivityController')
@php
    $rangeLabel = $week
        ? 'Week of '.$week->format('M j, Y')
        : ActivityController::PERIODS[$period];
    $query = fn (array $changes) => route('admin.activity.index', array_filter(array_merge(
        ['type' => $type, 'period' => $week ? null : $period, 'week' => $week?->toDateString()],
        $changes,
    )));
@endphp
{{-- Keeps the counts and lists current while the page is open (see partials/scripts) --}}
<div data-refresh-every="15" hidden></div>

<div class="admin-toolbar">
    <div>
        <h1 class="section-title" style="margin:0;">Activity</h1>
        <p class="panel-sub" style="margin:4px 0 0;">Reviews, ratings, diary logs, comments and lists from members · {{ $rangeLabel }}</p>
    </div>
    <a href="{{ route('admin.dashboard') }}" class="btn btn-secondary">← Dashboard</a>
</div>

<div class="filter-tabs" style="margin-bottom:10px;">
    @foreach(ActivityController::PERIODS as $key => $label)
        <a href="{{ $query(['period' => $key, 'week' => null]) }}" class="{{ ! $week && $period === $key ? 'active' : '' }}">{{ $label }}</a>
    @endforeach
    @if($week)
        <a href="{{ $query(['week' => null]) }}" class="active" title="Show this week's dates only; click to clear">Week of {{ $week->format('M j') }} ✕</a>
    @endif
</div>

<div class="filter-tabs" style="margin-bottom:14px;">
    @foreach(ActivityController::TYPES as $key => $label)
        <a href="{{ $query(['type' => $key]) }}" class="{{ $type === $key ? 'active' : '' }}">{{ $label }} <b>{{ number_format($counts[$key]) }}</b></a>
    @endforeach
</div>

<div class="table-wrap">
    <table class="admin-table">
        <thead>
            <tr>
                <th>When</th>
                <th>Member</th>
                @switch($type)
                    @case('reviews') <th>Film</th><th>Rating</th><th>Review</th> @break
                    @case('logs') <th>Film</th><th>Rating</th><th>Watched</th> @break
                    @case('comments') <th>On the review of</th><th>Comment</th> @break
                    @case('lists') <th>List</th><th style="text-align:right;">Films</th><th>Visibility</th> @break
                @endswitch
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($items as $item)
                <tr>
                    <td style="white-space:nowrap;" title="{{ $item->created_at->format('M d, Y g:i A') }}">{{ $item->created_at->diffForHumans() }}</td>
                    <td>
                        @if($item->user)
                            <a href="{{ route('admin.accounts.show', $item->user) }}">&#64;{{ $item->user->username ?? $item->user->name }}</a>
                        @else
                            <span class="text-muted">{{ $type === 'lists' ? 'FilmFolio Crew' : 'Deleted member' }}</span>
                        @endif
                    </td>
                    @switch($type)
                        @case('reviews')
                            <td><a href="{{ route('films.show', $item->film) }}">{{ $item->film->title }}</a></td>
                            <td>@include('partials.stars', ['rating' => $item->rating])</td>
                            <td class="text-muted" style="max-width:340px;">{{ $item->body ? \Illuminate\Support\Str::limit($item->body, 90) : 'Rating only' }}</td>
                            <td>
                                <div class="row-actions">
                                    <a href="{{ route('reviews.show', $item) }}" class="icon-btn icon-view" title="View" aria-label="View review">
                                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M1.5 12S5.5 5 12 5s10.5 7 10.5 7-4 7-10.5 7S1.5 12 1.5 12z"/><circle cx="12" cy="12" r="3"/></svg>
                                    </a>
                                    <form method="POST" action="{{ route('admin.reviews.destroy', $item) }}" onsubmit="return confirm(@js($item->body ? 'Remove this review?' : 'Remove this rating?'))">
                                        @csrf
                                        @method('DELETE')
                                        <button class="icon-btn icon-delete" title="Remove">🗑</button>
                                    </form>
                                </div>
                            </td>
                            @break
                        @case('logs')
                            <td><a href="{{ route('films.show', $item->film) }}">{{ $item->film->title }}</a></td>
                            <td>@if($item->rating) @include('partials.stars', ['rating' => $item->rating]) @else <span class="text-muted">—</span> @endif</td>
                            <td>{{ $item->watched_on?->format('M d, Y') }} @if($item->rewatch) <span class="badge" style="font-size:9px;">Rewatch</span> @endif</td>
                            <td>
                                <a href="{{ route('films.show', $item->film) }}" class="icon-btn icon-view" title="View film" aria-label="View film">
                                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M1.5 12S5.5 5 12 5s10.5 7 10.5 7-4 7-10.5 7S1.5 12 1.5 12z"/><circle cx="12" cy="12" r="3"/></svg>
                                </a>
                            </td>
                            @break
                        @case('comments')
                            <td>{{ $item->review?->film?->title ?? '—' }}</td>
                            <td class="text-muted" style="max-width:380px;">{{ \Illuminate\Support\Str::limit($item->body, 110) }}</td>
                            <td>
                                <div class="row-actions">
                                    @if($item->review)
                                        <a href="{{ route('reviews.show', $item->review) }}#comments" class="icon-btn icon-view" title="View" aria-label="View comment">
                                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M1.5 12S5.5 5 12 5s10.5 7 10.5 7-4 7-10.5 7S1.5 12 1.5 12z"/><circle cx="12" cy="12" r="3"/></svg>
                                        </a>
                                    @endif
                                    <form method="POST" action="{{ route('admin.comments.destroy', $item) }}" onsubmit="return confirm('Remove this comment?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="icon-btn icon-delete" title="Remove">🗑</button>
                                    </form>
                                </div>
                            </td>
                            @break
                        @case('lists')
                            <td>
                                <a href="{{ route('lists.show', $item) }}" style="color:#fff;font-weight:600;">{{ $item->name }}</a>
                                @if($item->is_official) <span class="badge">Crew Pick</span> @endif
                            </td>
                            <td style="text-align:right;">{{ $item->films_count }}</td>
                            <td>{{ ucfirst($item->visibility) }}</td>
                            <td>
                                <a href="{{ route('lists.show', $item) }}" class="icon-btn icon-view" title="View list" aria-label="View list">
                                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M1.5 12S5.5 5 12 5s10.5 7 10.5 7-4 7-10.5 7S1.5 12 1.5 12z"/><circle cx="12" cy="12" r="3"/></svg>
                                </a>
                            </td>
                            @break
                    @endswitch
                </tr>
            @empty
                <tr><td colspan="6" class="empty-state">Nothing in this period yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

{{ $items->links() }}
@endsection
