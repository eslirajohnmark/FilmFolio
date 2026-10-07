<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head', ['title' => trim($__env->yieldContent('title')) ?: config('app.name', 'FilmFolio')])
    @livewireStyles
</head>
<body>
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

    <main>
        @yield('content')
    </main>

    @include('partials.footer')

    @livewireScripts
    @include('partials.scripts')
</body>
</html>
