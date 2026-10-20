<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head', ['title' => trim($__env->yieldContent('title')) ?: config('app.name', 'FilmFolio')])
    @livewireStyles
</head>
<body class="has-tab-bar {{ request()->routeIs('home') ? 'is-home' : '' }}">
    @if(request()->routeIs('home'))
        @include('partials.intro')
    @endif
    @include('partials.navbar')

    @if(session('status'))
        @php
            $statusMessages = [
                'password-updated' => 'Password updated.',
                'verification-link-sent' => 'Verification link sent.',
                'profile-updated' => 'Profile updated.',
            ];
        @endphp
        <div class="toast">{{ $statusMessages[session('status')] ?? session('status') }}</div>
    @endif

    @include('partials.banner')

    <main>
        @yield('content')
    </main>

    @include('partials.footer')

    @include('partials.tab-bar')

    @livewireScripts
    @include('partials.scripts')
</body>
</html>
