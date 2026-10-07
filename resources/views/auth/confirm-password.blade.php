<x-guest-layout heading="Confirm Password" subheading="This is a secure area">
    <p class="text">Please confirm your password before continuing.</p>

    <form method="POST" action="{{ route('password.confirm') }}">
        @csrf

        <x-auth-input name="password" label="Password" type="password" icon="lock"
                      placeholder="Enter your password" required autocomplete="current-password" />

        <button type="submit" class="btn-submit">Confirm</button>
    </form>
</x-guest-layout>
