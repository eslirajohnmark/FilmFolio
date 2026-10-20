{{--
    Live checklist for a new password; ticks turn green as each rule is met.
    The input points at it with data-password-rules="{{ $id }}". The same rules are
    enforced on the server (Password::defaults() in AppServiceProvider).
--}}
<ul class="pw-rules" id="{{ $id }}" aria-live="polite">
    <li data-rule="length">At least 8 characters</li>
    <li data-rule="upper">One uppercase letter (A–Z)</li>
    <li data-rule="lower">One lowercase letter (a–z)</li>
    <li data-rule="number">One number (0–9)</li>
    <li data-rule="symbol">One special character (! @ # $ …)</li>
</ul>
