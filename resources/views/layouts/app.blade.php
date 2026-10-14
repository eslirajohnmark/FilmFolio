<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head', ['title' => trim($__env->yieldContent('title')) ?: config('app.name', 'FilmFolio')])
    @livewireStyles
</head>
<body>
    @include('partials.navbar')

    @include('partials.toasts')

    <main>
        @yield('content')
    </main>

    @include('partials.footer')

    @livewireScripts
    @include('partials.scripts')
</body>
</html>
