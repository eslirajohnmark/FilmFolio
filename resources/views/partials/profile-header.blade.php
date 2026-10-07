{{-- Expects $user with loadCount(['watchedFilms', 'filmLists', 'followers', 'following', 'diaryEntries as this_year_count']). --}}
<div class="profile-header">
    <img src="{{ $user->avatarUrl() }}" class="profile-avatar" alt="">

    <div class="profile-info">
        <h1>{{ $user->name }}</h1>
        <div class="review-author" style="font-size:13px;">&#64;{{ $user->username ?? $user->name }}</div>
        @if($user->bio)
            <p class="profile-bio">{{ $user->bio }}</p>
        @endif

        @auth
            @unless(auth()->user()->is($user))
                <div style="margin-top:12px;">
                    @livewire('follow-button', ['user' => $user], key('pf-'.$user->id))
                </div>
            @endunless
        @endauth
    </div>

    <div class="profile-stats" style="margin-left:auto;">
        <a href="{{ route('profile.show', [$user, 'tab' => 'films']) }}"><span><b>{{ number_format($user->watched_films_count) }}</b> Films</span></a>
        <a href="{{ route('profile.show', [$user, 'tab' => 'diary']) }}"><span><b>{{ number_format($user->this_year_count) }}</b> This year</span></a>
        <a href="{{ route('profile.show', [$user, 'tab' => 'lists']) }}"><span><b>{{ number_format($user->film_lists_count) }}</b> Lists</span></a>
        <span><b>{{ number_format($user->following_count) }}</b> Following</span>
        <span><b>{{ number_format($user->followers_count) }}</b> Followers</span>
    </div>

    @if(auth()->user()?->is($user))
        <a href="{{ route('profile.edit') }}" class="btn-edit-profile" style="margin-left:0;">Edit Profile</a>
    @endif
</div>
