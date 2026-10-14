<x-guest-layout heading="Welcome Back" subheading="Login to your account">

    <form method="POST" action="{{ route('login') }}" data-submit-once data-require-filled data-clear-on-back autocomplete="off">
        @csrf

        <x-auth-input name="login" label="Username" icon="mail"
                      placeholder="Enter your username or email"
                      required autofocus autocomplete="username" />

        <x-auth-input name="password" label="Password" type="password" icon="lock"
                      placeholder="Enter your Password"
                      required autocomplete="current-password" />

        <div class="auth-row">
            <label><input type="checkbox" name="remember"> Remember me</label>
            <a href="{{ route('password.request') }}">Forgot password?</a>
        </div>

        <button type="submit" class="btn-submit" data-loading-text="Logging in…">Login</button>
    </form>

    <p class="hint">Don't have an account?<a href="{{ route('register') }}">Sign up</a></p>
</x-guest-layout>
