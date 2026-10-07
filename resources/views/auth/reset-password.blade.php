<x-guest-layout heading="Reset Password" subheading="Choose a new password for your account">
    <form method="POST" action="{{ route('password.store') }}" data-submit-once>
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <x-auth-input name="email" label="Email" type="email" icon="mail" :value="$request->email"
                      required autocomplete="username" :readonly="filled($request->email)" />

        <x-auth-input name="password" label="New Password" type="password" icon="lock"
                      placeholder="At least 8 characters" required autofocus autocomplete="new-password" />

        <x-auth-input name="password_confirmation" label="Confirm Password" type="password" icon="lock"
                      placeholder="Type it again" required autocomplete="new-password" />

        <button type="submit" class="btn-submit" data-loading-text="Saving…">Reset Password</button>
    </form>

    <p class="hint">Link expired?<a href="{{ route('password.request') }}">Send a new one</a></p>
</x-guest-layout>
