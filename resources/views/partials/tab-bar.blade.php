{{--
    Phone tab bar, fixed to the bottom of the screen. Hidden on wider screens (see _tab-bar.css),
    where the top navbar does the job. Pass ['admin' => true] for the admin area's tabs.
--}}
@php
    $isAdminArea = $admin ?? false;
    $member = auth()->user();
    $viewingOwnProfile = $member
        && request()->routeIs('profile.show')
        && optional(request()->route('user'))->is($member);

    $tabs = $isAdminArea
        ? [
            ['label' => 'Dashboard', 'href' => route('admin.dashboard'), 'icon' => 'grid', 'active' => request()->routeIs('admin.dashboard')],
            ['label' => 'Films', 'href' => route('admin.films.index'), 'icon' => 'film', 'active' => request()->routeIs('admin.films.index', 'admin.films.edit')],
            ['label' => 'Add film', 'href' => route('admin.films.create'), 'icon' => 'plus', 'active' => request()->routeIs('admin.films.create'), 'primary' => true],
            ['label' => 'Lists', 'href' => route('admin.lists.index'), 'icon' => 'list', 'active' => request()->routeIs('admin.lists.*')],
            ['label' => 'Accounts', 'href' => route('admin.accounts.index'), 'icon' => 'users', 'active' => request()->routeIs('admin.accounts.*')],
        ]
        : [
            ['label' => 'Home', 'href' => route('home'), 'icon' => 'home', 'active' => request()->routeIs('home')],
            ['label' => 'Films', 'href' => route('films.index'), 'icon' => 'film', 'active' => request()->routeIs('films.*', 'reviews.*', 'tags.*')],
            ['label' => 'Search', 'href' => route('search'), 'icon' => 'search', 'active' => request()->routeIs('search'), 'primary' => true],
            ['label' => 'Lists', 'href' => route('lists.index'), 'icon' => 'list', 'active' => request()->routeIs('lists.*')],
            $member
                ? ['label' => 'Profile', 'href' => route('profile.show', $member), 'icon' => 'avatar', 'active' => $viewingOwnProfile || request()->routeIs('profile.edit', 'diary.*', 'watchlist.*', 'feed.*')]
                : ['label' => 'Sign in', 'href' => route('login'), 'icon' => 'user', 'active' => request()->routeIs('login', 'register', 'password.*')],
        ];
@endphp

<nav class="tab-bar" aria-label="{{ $isAdminArea ? 'Admin' : 'Main' }} navigation">
    @foreach($tabs as $tab)
        <a href="{{ $tab['href'] }}"
           class="tab-bar-item {{ ($tab['primary'] ?? false) ? 'tab-bar-primary' : '' }} {{ $tab['active'] ? 'active' : '' }}"
           @if($tab['active']) aria-current="page" @endif>
            @if($tab['icon'] === 'avatar')
                <img src="{{ $member->avatarUrl() }}" class="tab-bar-avatar" alt="">
            @else
                <span class="{{ ($tab['primary'] ?? false) ? 'tab-bar-fab' : '' }}">
                    <svg class="tab-bar-icon" viewBox="0 0 24 24" aria-hidden="true">
                        @switch($tab['icon'])
                            @case('home')
                                <path d="M4 10.5 12 4l8 6.5V19a1 1 0 0 1-1 1h-4.5v-5.5h-5V20H5a1 1 0 0 1-1-1z"/>
                                @break
                            @case('film')
                                <rect x="3.5" y="5" width="17" height="14" rx="2"/>
                                <path d="M7.5 5v14M16.5 5v14M3.5 9.7h4M3.5 14.3h4M16.5 9.7h4M16.5 14.3h4"/>
                                @break
                            @case('search')
                                <circle cx="11" cy="11" r="6.5"/>
                                <path d="m20 20-4.2-4.2"/>
                                @break
                            @case('list')
                                <path d="M9 6.5h11M9 12h11M9 17.5h11"/>
                                <path d="M4.5 6.5h.01M4.5 12h.01M4.5 17.5h.01" stroke-width="2.6"/>
                                @break
                            @case('user')
                                <circle cx="12" cy="8" r="3.8"/>
                                <path d="M4.5 20c.8-3.6 3.8-5.5 7.5-5.5s6.7 1.9 7.5 5.5"/>
                                @break
                            @case('grid')
                                <rect x="4" y="4" width="6.5" height="6.5" rx="1.6"/>
                                <rect x="13.5" y="4" width="6.5" height="6.5" rx="1.6"/>
                                <rect x="4" y="13.5" width="6.5" height="6.5" rx="1.6"/>
                                <rect x="13.5" y="13.5" width="6.5" height="6.5" rx="1.6"/>
                                @break
                            @case('plus')
                                <path d="M12 5v14M5 12h14"/>
                                @break
                            @case('users')
                                <circle cx="9" cy="8" r="3.4"/>
                                <path d="M3 19.5c.7-3.2 3.1-5 6-5s5.3 1.8 6 5"/>
                                <path d="M15.5 4.8a3.3 3.3 0 0 1 0 6.4M17.5 14.7c1.8.6 3 2.2 3.5 4.8"/>
                                @break
                        @endswitch
                    </svg>
                </span>
            @endif
            <span class="tab-bar-label">{{ $tab['label'] }}</span>
        </a>
    @endforeach
</nav>
