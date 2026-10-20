{{--
    Top-of-page notification. Green for success, red for errors.
    Success: flash ->with('banner', ['type' => 'success', 'message' => '...']).
    Errors on sign-in/sign-up pages: pass $errorBanner; it shows whenever the form has errors.
--}}
@php
    $banner = session('banner');

    if (! $banner && filled($errorBanner ?? null) && $errors->any()) {
        $banner = ['type' => 'error', 'message' => $errorBanner];
    }
@endphp
@if(is_array($banner) && filled($banner['message'] ?? null))
    @php $isError = ($banner['type'] ?? 'success') === 'error'; @endphp
    <div class="banner {{ $isError ? 'banner-error' : 'banner-success' }}" role="{{ $isError ? 'alert' : 'status' }}" data-banner>
        <span class="banner-icon" aria-hidden="true">
            @if($isError)
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
            @else
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12.5 4.5 4.5L19 7.5"/></svg>
            @endif
        </span>
        <span class="banner-message">{{ $banner['message'] }}</span>
        <button type="button" class="banner-close" aria-label="Dismiss">&times;</button>
    </div>
@endif
