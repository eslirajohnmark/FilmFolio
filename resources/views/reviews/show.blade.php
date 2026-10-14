@extends('layouts.app')

@section('title', ($review->user->username ?? $review->user->name)."'s review of ".$review->film->title.' · FilmFolio')

@section('content')
@php $film = $review->film; $author = $review->user; @endphp
<div class="container" style="max-width:900px;">
    <div style="display:grid;grid-template-columns:150px 1fr;gap:28px;align-items:start;" class="review-page">
        @include('partials.poster-card', ['film' => $film])

        <div>
            <div class="review-footer" style="margin-bottom:10px;">
                <img src="{{ $author->avatarUrl() }}" class="activity-avatar" style="width:28px;height:28px;" alt="">
                <span>Review by <a href="{{ route('profile.show', $author) }}" class="review-author">{{ $author->username ?? $author->name }}</a></span>
            </div>

            <h1 class="film-title" style="font-size:30px;">
                <a href="{{ route('films.show', $film) }}">{{ $film->title }}</a><span class="year">{{ $film->year }}</span>
            </h1>

            <div style="margin:8px 0 18px;">
                @include('partials.stars', ['rating' => $review->rating])
                <span style="font-size:12px;color:var(--text-muted);margin-left:8px;">{{ $review->created_at->format('M d, Y') }}</span>
            </div>

            @if($review->body)
                <p class="review-text full">{{ $review->body }}</p>
            @else
                <p class="empty-state">Rated without a written review.</p>
            @endif

            <div class="review-footer" style="margin-top:18px;">
                @livewire('review-vote', ['review' => $review], key('rv-vote-'.$review->id))
                @include('partials.review-owner-actions', ['review' => $review])
            </div>
        </div>
    </div>

    <div id="comments" class="section-label">{{ $review->comments_count }} {{ Str::plural('comment', $review->comments_count) }}</div>

    @foreach($review->comments as $comment)
        <div class="comment">
            <img src="{{ $comment->user->avatarUrl() }}" alt="">
            <div class="comment-body">
                <div class="comment-meta">
                    <a href="{{ route('profile.show', $comment->user) }}" class="review-author">{{ $comment->user->username ?? $comment->user->name }}</a>
                    <span>{{ $comment->created_at->diffForHumans() }}</span>
                    @can('delete', $comment)
                        <form method="POST" action="{{ route('comments.destroy', $comment) }}" onsubmit="return confirm('Delete this comment?')">
                            @csrf
                            @method('DELETE')
                            <button class="btn-ghost" style="font-size:12px;">Delete</button>
                        </form>
                    @elseif(auth('admin')->check())
                        <form method="POST" action="{{ route('admin.comments.destroy', $comment) }}" onsubmit="return confirm('Remove this comment? (admin)')">
                            @csrf
                            @method('DELETE')
                            <button class="btn-ghost" style="font-size:12px;">Remove (admin)</button>
                        </form>
                    @endcan
                </div>
                <div class="comment-text">{{ $comment->body }}</div>
            </div>
        </div>
    @endforeach

    @auth
        <form method="POST" action="{{ route('reviews.comments.store', $review) }}" class="panel panel-form">
            @csrf
            <div class="field">
                <label for="commentBody">Reply as &#64;{{ auth()->user()->username ?? auth()->user()->name }}</label>
                <textarea id="commentBody" name="body" required maxlength="2000" style="min-height:80px;">{{ old('body') }}</textarea>
                @error('body') <div class="field-error">{{ $message }}</div> @enderror
            </div>
            <button type="submit" class="btn btn-primary">Post comment</button>
        </form>
    @else
        <p class="empty-state"><a href="{{ route('login') }}">Log in</a> to leave a comment.</p>
    @endauth

    @if($moreFromFilm->isNotEmpty())
        <div class="section-label">More reviews of {{ $film->title }}</div>
        <div class="review-grid">
            @foreach($moreFromFilm as $other)
                @include('partials.review-card', ['review' => $other, 'showFilm' => false])
            @endforeach
        </div>
    @endif
</div>
@endsection
