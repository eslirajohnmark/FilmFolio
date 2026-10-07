{{--
    $user          User with reviews_count, film_lists_count, followers_count
    $followingIds  list<int> of members the viewer follows
    $keyPrefix     string to keep Livewire keys unique per section
--}}
<div class="member-card" wire:key="{{ $keyPrefix }}-{{ $user->id }}">
    <a href="{{ route('profile.show', $user) }}" class="member-avatar-link">
        <img src="{{ $user->avatarUrl() }}" class="member-avatar" alt="">
    </a>

    <div class="member-info">
        <div class="name">
            <a href="{{ route('profile.show', $user) }}">&#64;{{ $user->username ?? $user->name }}</a>
        </div>
        <div class="stats" title="{{ $user->reviews_count }} reviews · {{ $user->film_lists_count }} lists · {{ $user->followers_count }} followers">
            {{ Number::abbreviate($user->reviews_count, maxPrecision: 1) }} {{ Str::plural('Review', $user->reviews_count) }} ·
            {{ Number::abbreviate($user->film_lists_count, maxPrecision: 1) }} {{ Str::plural('List', $user->film_lists_count) }} ·
            {{ Number::abbreviate($user->followers_count, maxPrecision: 1) }} {{ Str::plural('Follower', $user->followers_count) }}
        </div>
        @livewire('follow-button', [
            'user' => $user,
            'followersCount' => $user->followers_count,
            'following' => in_array($user->id, $followingIds, true),
        ], key($keyPrefix.'-fb-'.$user->id))
    </div>
</div>
