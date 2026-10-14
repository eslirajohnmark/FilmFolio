<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head', ['title' => $heading.' · FilmFolio'])
</head>
<body class="auth-page">
    <main class="auth-wrapper">
        <a href="{{ route('home') }}" class="auth-close" aria-label="Close">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><path d="M5 5l14 14M19 5 5 19"/></svg>
        </a>

        <div class="auth-card {{ $admin ? 'admin' : '' }}">
            <a href="{{ route('home') }}" class="auth-logo">@include('partials.logo', ['mark' => 'film'])</a>

            <h2>{{ $heading }}</h2>
            @if($subheading)
                <p class="auth-tagline">{{ $subheading }}</p>
            @endif

            {{ $slot }}
        </div>
    </main>

    @include('partials.toasts')
    @include('partials.scripts')
</body>
</html>
