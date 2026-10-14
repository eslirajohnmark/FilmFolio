<x-guest-layout heading="Forgot Password?" subheading="Enter your email or username and we'll send you a reset link">
    @if (session('status'))
        <div class="notice">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('password.email') }}" data-submit-once data-clear-on-back autocomplete="off">
        @csrf

        <x-auth-input name="email" label="Email or Username" icon="mail"
                      placeholder="you@example.com or your username" required autofocus autocomplete="username" />

        <button type="submit" class="btn-submit" data-loading-text="Sending…">Send Reset Link</button>
    </form>

    <p class="hint">Remembered it?<a href="{{ route('login') }}">Back to login</a></p>
</x-guest-layout>
