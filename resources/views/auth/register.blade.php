<x-guest-layout heading="Create Account" subheading="Join the FilmFolio community">
    <form method="POST" action="{{ route('register') }}" data-submit-once data-require-filled data-clear-on-back autocomplete="off">
        @csrf

        <x-auth-input name="name" label="Full Name" icon="user"
                      placeholder="Enter your name" required autofocus autocomplete="name" />

        <x-auth-input name="username" label="Username" icon="at"
                      placeholder="Enter your username" required autocomplete="nickname" />

        <x-auth-input name="email" label="Email" type="email" icon="mail"
                      placeholder="Used if you forget your password" required autocomplete="email" />

        <x-auth-input name="password" label="Password" type="password" icon="lock"
                      placeholder="Enter your password" required autocomplete="new-password" />

        <ul class="password-rules" data-password-rules="password" aria-live="polite" hidden>
            <li data-rule=".{8}">At least 8 characters</li>
            <li data-rule="\p{Lu}">One uppercase letter</li>
            <li data-rule="\p{Ll}">One lowercase letter</li>
            <li data-rule="\p{N}">One number</li>
            <li data-rule="[\p{Z}\p{S}\p{P}]">One special character (e.g. ! @ # ?)</li>
        </ul>

        <x-auth-input name="password_confirmation" label="Confirm Password" type="password" icon="lock"
                      placeholder="Confirm your password" required autocomplete="new-password" />

        <button type="submit" class="btn-submit" data-loading-text="Creating account…">Register</button>
    </form>

    <p class="hint">Already have an account?<a href="{{ route('login') }}">Login</a></p>
</x-guest-layout>
