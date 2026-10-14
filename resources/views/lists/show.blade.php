@extends('layouts.app')

@section('title', $list->name.' · FilmFolio')

@section('content')
<div class="container">
    <div class="review-footer" style="margin-bottom:8px;">
        <img src="{{ $list->user?->avatarUrl() ?? asset('images/default-avatar.svg') }}" class="activity-avatar" style="width:28px;height:28px;" alt="">
        <span>
            {{ $list->is_official ? 'Crew Pick by' : 'List by' }}
            @if($list->user)
                <a href="{{ route('profile.show', $list->user) }}" class="review-author">{{ $list->ownerName() }}</a>
            @else
                <span class="review-author">{{ $list->ownerName() }}</span>
            @endif
        </span>
    </div>
    <h1 class="film-title" style="font-size:32px;">{{ $list->name }}</h1>
    <p class="film-meta">
        <span>{{ $films->count() }} {{ Str::plural('FILM', $films->count()) }}</span>
        <span>{{ strtoupper($list->visibility) }}</span>
        @if($list->genre)<span>{{ strtoupper($list->genre) }}</span>@endif
        <span>UPDATED {{ strtoupper($list->updated_at->format('M d, Y')) }}</span>
    </p>

    @if($list->description)
        <p class="film-synopsis" style="max-width:760px;">{{ $list->description }}</p>
    @endif

    <div style="margin-bottom:22px;">
        @livewire('list-like', ['list' => $list], key('ll-'.$list->id))
    </div>

    @can('update', $list)
        <form id="edit" method="POST" action="{{ route('lists.update', $list) }}" class="panel panel-form" style="max-width:640px;margin-bottom:24px;">
            @csrf
            @method('PATCH')
            <div class="field">
                <label for="listName">Name</label>
                <input id="listName" type="text" name="name" value="{{ old('name', $list->name) }}" required>
            </div>
            <div class="field">
                <label for="listDescription">Description</label>
                <textarea id="listDescription" name="description" maxlength="1000" style="min-height:70px;">{{ old('description', $list->description) }}</textarea>
            </div>
            <div class="actions">
                <select name="visibility" aria-label="Visibility" class="input" style="width:auto;">
                    <option value="public" @selected($list->visibility === 'public')>Public</option>
                    <option value="private" @selected($list->visibility === 'private')>Private</option>
                </select>
                <button class="btn btn-primary">Save</button>
            </div>
            @if($errors->any())
                <div class="error-list" style="margin-top:12px;">{{ $errors->first() }}</div>
            @endif
        </form>
    @endcan

    <div class="poster-grid">
        @forelse($films as $film)
            <div>
                @include('partials.poster-card', ['film' => $film])
                @can('update', $list)
                    <form method="POST" action="{{ route('lists.films.destroy', [$list, $film]) }}" style="margin-top:6px;text-align:center;">
                        @csrf
                        @method('DELETE')
                        <button class="btn-ghost" style="font-size:12px;">✕ Remove</button>
                    </form>
                @endcan
            </div>
        @empty
            <p class="empty-state">No films yet. Use “+ Add to List” on any film page.</p>
        @endforelse
    </div>

    @if($reviews->isNotEmpty())
        <div class="section-label">Reviews of films in this list</div>
        <div class="review-grid">
            @foreach($reviews as $review)
                @include('partials.review-card', ['review' => $review])
            @endforeach
        </div>
    @endif
</div>
@endsection
