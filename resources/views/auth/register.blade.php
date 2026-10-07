<x-guest-layout heading="Create Account" subheading="Join the FilmFolio community">
    <form method="POST" action="{{ route('register') }}" data-submit-once>
        @csrf

        <x-auth-input name="name" label="Full Name" icon="user"
                      placeholder="Enter your name" required autofocus autocomplete="name" />

        <x-auth-input name="username" label="Username" icon="at"
                      placeholder="Enter your username" required autocomplete="nickname" />

        <x-auth-input name="email" label="Email" type="email" icon="mail"
                      placeholder="Used if you forget your password" required autocomplete="email" />

        <x-auth-input name="password" label="Password" type="password" icon="lock"
                      placeholder="Enter your password" required autocomplete="new-password" />

        <x-auth-input name="password_confirmation" label="Confirm Password" type="password" icon="lock"
                      placeholder="Confirm your password" required autocomplete="new-password" />

        <button type="submit" class="btn-submit" data-loading-text="Creating account…">Register</button>
    </form>

    <p class="hint">Already have an account?<a href="{{ route('login') }}">Login</a></p>
</x-guest-layout>
