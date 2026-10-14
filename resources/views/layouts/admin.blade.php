<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head', ['title' => (trim($__env->yieldContent('title')) ?: 'Admin').' · FilmFolio Admin'])
    @livewireStyles
</head>
<body class="admin">
    <nav class="navbar admin-bar" id="navbar">
        <a href="{{ route('admin.dashboard') }}" class="brand">
            @include('partials.logo')
            <span class="badge red" style="margin-left:2px;">Admin</span>
        </a>

        <button type="button" class="nav-toggle" aria-label="Menu"
                onclick="document.getElementById('navbar').classList.toggle('open')">☰</button>

        <ul class="nav-links">
            <li><a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">Dashboard</a></li>
            <li><a href="{{ route('admin.films.index') }}" class="{{ request()->routeIs('admin.films.*') ? 'active' : '' }}">Films</a></li>
            <li><a href="{{ route('admin.lists.index') }}" class="{{ request()->routeIs('admin.lists.*') ? 'active' : '' }}">Lists</a></li>
            <li><a href="{{ route('admin.accounts.index') }}" class="{{ request()->routeIs('admin.accounts.*') ? 'active' : '' }}">Accounts</a></li>
        </ul>

        <div class="nav-right">
            <a href="{{ route('home') }}" class="nav-link-plain">View site ↗</a>
            <img src="{{ auth('admin')->user()->avatarUrl() }}" class="nav-avatar" alt="" title="&#64;{{ auth('admin')->user()->username }}">
            <form method="POST" action="{{ route('admin.logout') }}">
                @csrf
                <button type="submit" class="nav-link-plain">Sign out</button>
            </form>
        </div>
    </nav>

    @include('partials.toasts')

    <main class="container admin-main">
        @yield('content')
    </main>

    @livewireScripts
    @include('partials.scripts')
</body>
</html>
