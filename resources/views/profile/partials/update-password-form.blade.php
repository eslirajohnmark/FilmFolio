<section class="panel">
    <h2>Password</h2>
    <p class="panel-sub">Use a long, random password to keep your account secure.</p>

    <form method="post" action="{{ route('password.update') }}" class="panel-form">
        @csrf
        @method('put')

        <div class="field">
            <label for="update_password_current_password">Current password</label>
            <input id="update_password_current_password" name="current_password" type="password" autocomplete="current-password">
            @error('current_password', 'updatePassword') <div class="field-error">{{ $message }}</div> @enderror
        </div>

        <div class="field">
            <label for="update_password_password">New password</label>
            <input id="update_password_password" name="password" type="password" autocomplete="new-password">
            @error('password', 'updatePassword') <div class="field-error">{{ $message }}</div> @enderror
        </div>

        <div class="field">
            <label for="update_password_password_confirmation">Confirm password</label>
            <input id="update_password_password_confirmation" name="password_confirmation" type="password" autocomplete="new-password">
            @error('password_confirmation', 'updatePassword') <div class="field-error">{{ $message }}</div> @enderror
        </div>

        <div class="actions">
            <button type="submit" class="btn btn-primary">Update password</button>
            @if (session('status') === 'password-updated')
                <span class="saved">Saved.</span>
            @endif
        </div>
    </form>
</section>
