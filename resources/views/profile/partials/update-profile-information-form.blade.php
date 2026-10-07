<section class="panel">
    <h2>Profile</h2>
    <p class="panel-sub">How you appear to other members.</p>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="panel-form">
        @csrf
        @method('patch')

        <div class="field" style="display:flex;gap:16px;align-items:center;">
            <img src="{{ $user->avatarUrl() }}" alt="" class="member-avatar" style="width:64px;height:64px;">
            <div style="flex:1;">
                <label for="avatar">Avatar</label>
                <input id="avatar" name="avatar" type="file" accept="image/jpeg,image/png,image/webp">
                @error('avatar') <div class="field-error">{{ $message }}</div> @enderror
            </div>
        </div>

        <div class="field">
            <label for="name">Full name</label>
            <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" required autocomplete="name">
            @error('name') <div class="field-error">{{ $message }}</div> @enderror
        </div>

        <div class="field">
            <label for="username">Username</label>
            <input id="username" name="username" type="text" value="{{ old('username', $user->username) }}" autocomplete="nickname">
            @error('username') <div class="field-error">{{ $message }}</div> @enderror
        </div>

        <div class="field">
            <label for="bio">Bio</label>
            <textarea id="bio" name="bio" maxlength="500" style="min-height:80px;">{{ old('bio', $user->bio) }}</textarea>
            @error('bio') <div class="field-error">{{ $message }}</div> @enderror
        </div>

        <div class="field">
            <label for="email">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required autocomplete="email">
            @error('email') <div class="field-error">{{ $message }}</div> @enderror

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <p class="panel-sub" style="margin:8px 0 0;">
                    Your email address is unverified.
                    <button form="send-verification" class="btn-ghost link">Re-send the verification email.</button>
                </p>
                @if (session('status') === 'verification-link-sent')
                    <p class="saved">A new verification link has been sent to your email address.</p>
                @endif
            @endif
        </div>

        <div class="actions">
            <button type="submit" class="btn btn-primary">Save profile</button>
        </div>
    </form>
</section>
