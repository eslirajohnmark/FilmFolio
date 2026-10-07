<x-guest-layout heading="Admin Sign In" subheading="Administrator access only" :admin="true">
    @if (session('status'))
        <div class="notice">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('admin.login.submit') }}">
        @csrf

        <x-auth-input name="login" label="Admin Username" icon="user"
                      placeholder="Username or email" required autofocus autocomplete="username" />

        <x-auth-input name="password" label="Password" type="password" icon="lock"
                      placeholder="Enter your password" required autocomplete="current-password" />

        <div class="auth-row">
            <label><input type="checkbox" name="remember"> Keep me signed in</label>
        </div>

        <button type="submit" class="btn-submit">Access Admin Panel</button>
    </form>

    <p class="hint"><a href="{{ route('home') }}" style="margin:0;">← Back to FilmFolio</a></p>
</x-guest-layout>
