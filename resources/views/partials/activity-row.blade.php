@php $user = $activity->user; $subject = $activity->subject; @endphp
<div class="activity-row" wire:key="act-{{ $activity->id }}">
    <img src="{{ $user->avatarUrl() }}" class="activity-avatar" alt="">

    <div class="activity-content">
        <a href="{{ route('profile.show', $user) }}" class="activity-user">
            &#64;{{ $user->username ?? $user->name }}
        </a>

        @switch($activity->type)
            @case('rated')
                <span>rated</span>
                <a href="{{ route('films.show', $subject) }}">{{ $subject->title }}</a>
                <span class="stars">{{ str_repeat('★', (int) ($activity->meta['rating'] ?? 0)) }}</span>
                @break

            @case('reviewed')
                <span>reviewed</span>
                <a href="{{ route('films.show', $subject) }}">{{ $subject->title }}</a>
                @if(! empty($activity->meta['body']))
                    <div class="activity-snippet">"{{ Str::limit($activity->meta['body'], 140) }}"</div>
                @endif
                @break

            @case('watched')
                <span>watched</span>
                <a href="{{ route('films.show', $subject) }}">{{ $subject->title }}</a>
                @if($activity->meta['rewatch'] ?? false)
                    <span class="badge" style="font-size:10px;">Rewatch</span>
                @endif
                @break

            @case('watchlist_added')
                <span>added</span>
                <a href="{{ route('films.show', $subject) }}">{{ $subject->title }}</a>
                <span>to their watchlist</span>
                @break

            @case('liked_film')
                <span>liked</span>
                <a href="{{ route('films.show', $subject) }}">{{ $subject->title }}</a>
                @break

            @case('commented')
                <span>commented on a review of</span>
                <a href="{{ route('reviews.show', $subject) }}#comments">{{ $activity->meta['film_title'] ?? 'a film' }}</a>
                @break

            @case('agreed_review')
                <span>agreed with a review of</span>
                <a href="{{ route('reviews.show', $subject) }}">{{ $activity->meta['film_title'] ?? 'a film' }}</a>
                @break

            @case('liked_review')
                <span>liked a review of</span>
                <strong style="color:#fff;">{{ $activity->meta['film_title'] ?? '' }}</strong>
                @break

            @case('followed')
                <span>started following</span>
                <a href="{{ route('profile.show', $subject) }}">&#64;{{ $subject->username ?? $subject->name }}</a>
                @break

            @case('listed')
                <span>created list</span>
                <a href="{{ route('lists.show', $subject) }}">{{ $subject->name }}</a>
                @break
        @endswitch

        <div class="activity-time">{{ $activity->created_at->diffForHumans() }}</div>
    </div>
</div>
