<section class="panel">
    <h2>Email notifications</h2>
    <p class="panel-sub">Emails go to {{ $user->email }}. Password reset emails are always sent.</p>

    <form method="post" action="{{ route('profile.notifications') }}" class="panel-form">
        @csrf
        @method('patch')

        <label class="checkbox" style="font-size:13px;">
            <input type="checkbox" name="email_notifications" value="1" @checked($user->email_notifications)>
            Email me when someone follows me or comments on my reviews
        </label>

        <div class="actions" style="margin-top:14px;">
            <button type="submit" class="btn btn-primary">Save</button>
        </div>
    </form>
</section>
