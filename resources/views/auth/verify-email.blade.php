<x-guest-layout heading="Verify Your Email" subheading="One last step">
    <p class="text">
        Thanks for signing up! Please verify your email address by clicking the link we just emailed you.
        Didn't get it? We'll send another.
    </p>

    @if (session('status') == 'verification-link-sent')
        <div class="notice">A new verification link has been sent to your email address.</div>
    @endif

    <form method="POST" action="{{ route('verification.send') }}">
        @csrf
        <button type="submit" class="btn-submit">Resend Verification Email</button>
    </form>

    <form method="POST" action="{{ route('logout') }}" class="hint">
        @csrf
        <button type="submit" class="btn-ghost">Log out</button>
    </form>
</x-guest-layout>
