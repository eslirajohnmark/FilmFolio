<x-guest-layout heading="Reset Password" subheading="Choose a new password for your account"
                error-banner="Password reset failed. Please check the highlighted fields.">
    <form method="POST" action="{{ route('password.store') }}" data-submit-once data-clear-on-back data-require-filled autocomplete="off">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <x-auth-input name="email" label="Email" type="email" icon="mail" :value="$request->email"
                      required autocomplete="username" :readonly="filled($request->email)" />

        <x-auth-input name="password" label="New Password" type="password" icon="lock"
                      placeholder="Enter a new password" required autofocus autocomplete="new-password" :checklist="true" />

        <x-auth-input name="password_confirmation" label="Confirm Password" type="password" icon="lock"
                      placeholder="Type it again" required autocomplete="new-password" matches="password" />

        <button type="submit" class="btn-submit" data-loading-text="Saving…">Reset Password</button>
    </form>

    <p class="hint">Link expired?<a href="{{ route('password.request') }}">Send a new one</a></p>
</x-guest-layout>
