{{-- Top-right toasts: green for ->with('status'), red for ->with('error') and validation errors.
     Livewire adds more via $this->dispatch('notify', message: '...', type: 'error'); see partials.scripts. --}}
@php
    $statusMessages = [
        'password-updated' => 'Password updated.',
        'verification-link-sent' => 'Verification link sent.',
        'profile-updated' => 'Profile updated.',
    ];
    // These pages already explain the status inline, and it is too long to read in a 4-second toast.
    $showStatus = session('status') && ! request()->routeIs('password.request', 'verification.notice');
@endphp
<div id="toasts" class="toasts" aria-live="polite">
    @if($showStatus)
        <div class="toast success" role="status">{{ $statusMessages[session('status')] ?? session('status') }}</div>
    @endif
    @if(session('error'))
        <div class="toast error" role="alert">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="toast error" role="alert">{{ $errors->first() }}</div>
    @endif
</div>
