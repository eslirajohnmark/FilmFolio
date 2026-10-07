<section class="panel danger">
    <h2>Delete account</h2>
    <p class="panel-sub">
        This permanently deletes your profile, diary, reviews and lists. It cannot be undone.
    </p>

    <details @if($errors->userDeletion->isNotEmpty()) open @endif>
        <summary class="btn btn-danger" style="list-style:none;">Delete my account…</summary>

        <form method="post" action="{{ route('profile.destroy') }}" class="panel-form" style="margin-top:16px;">
            @csrf
            @method('delete')

            <div class="field">
                <label for="delete_password">Enter your password to confirm</label>
                <input id="delete_password" name="password" type="password" placeholder="Password" autocomplete="current-password">
                @error('password', 'userDeletion') <div class="field-error">{{ $message }}</div> @enderror
            </div>

            <button type="submit" class="btn btn-danger">Permanently delete account</button>
        </form>
    </details>
</section>
