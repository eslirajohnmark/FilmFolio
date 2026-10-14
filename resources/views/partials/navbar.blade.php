<nav class="navbar" id="navbar">
    <a href="{{ route('home') }}" class="brand">
        @include('partials.logo')
    </a>

    <button type="button" class="nav-toggle" aria-label="Menu"
            onclick="document.getElementById('navbar').classList.toggle('open')">☰</button>

    <ul class="nav-links">
        <li><a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'active' : '' }}">Home</a></li>
        <li><a href="{{ route('films.index') }}" class="{{ request()->routeIs('films.*', 'search', 'reviews.*') ? 'active' : '' }}">Films</a></li>
        <li><a href="{{ route('lists.index') }}" class="{{ request()->routeIs('lists.*') ? 'active' : '' }}">Lists</a></li>
        <li><a href="{{ route('members.index') }}" class="{{ request()->routeIs('members.*') ? 'active' : '' }}">Members</a></li>
        <li><a href="{{ route('teasers.index') }}" class="{{ request()->routeIs('teasers.*') ? 'active' : '' }}">Teasers</a></li>
    </ul>

    <div class="nav-right">
        @php $searchQuery = request()->routeIs('search') ? request('q') : ''; @endphp
        <form action="{{ route('search') }}" method="GET" class="nav-search {{ $searchQuery ? 'open' : '' }}" role="search">
            <button type="button" class="nav-search-toggle" aria-label="Search">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
            </button>
            <input type="search" name="q" value="{{ $searchQuery }}"
                   placeholder="Search films…" aria-label="Search films">
        </form>

        @if(auth('admin')->check())
            <a href="{{ route('admin.dashboard') }}" class="nav-link-plain" title="Admin dashboard">Admin</a>
        @endif

        @guest
            <a href="{{ route('login') }}" class="nav-link-plain">Login</a>
            <a href="{{ route('register') }}" class="btn-register">Register</a>
        @endguest

        @auth

            <details class="nav-menu">
                <summary aria-label="Account menu">
                    <img src="{{ auth()->user()->avatarUrl() }}" class="nav-avatar" alt="">
                </summary>
                <div class="nav-menu-panel">
                    <div class="nav-menu-name">&#64;{{ auth()->user()->username ?? auth()->user()->name }}</div>
                    <a href="{{ route('profile.show', auth()->user()) }}">Profile</a>
                    <a href="{{ route('profile.show', [auth()->user(), 'tab' => 'films']) }}">Films</a>
                    <a href="{{ route('diary.index') }}">Diary</a>
                    <a href="{{ route('watchlist.index') }}">Watchlist</a>
                    <a href="{{ route('feed.index') }}">Activity</a>
                    <a href="{{ route('profile.edit') }}">Settings</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit">Sign out</button>
                    </form>
                </div>
            </details>
        @endauth
    </div>
</nav>
