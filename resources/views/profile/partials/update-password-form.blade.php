<section class="panel">
    <h2>Password</h2>
    <p class="panel-sub">At least 8 characters, with an uppercase and a lowercase letter, a number and a special character.</p>

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
            <input id="update_password_password" name="password" type="password" autocomplete="new-password"
                   data-password-rules="update-password-rules">
            @include('partials.password-rules', ['id' => 'update-password-rules'])
            @error('password', 'updatePassword') <div class="field-error">{{ $message }}</div> @enderror
        </div>

        <div class="field">
            <label for="update_password_password_confirmation">Confirm password</label>
            <input id="update_password_password_confirmation" name="password_confirmation" type="password" autocomplete="new-password"
                   data-matches="update_password_password">
            <div class="pw-match" id="update_password_password_confirmation-match" aria-live="polite"></div>
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
