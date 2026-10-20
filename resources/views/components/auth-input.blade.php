@props([
    'name',
    'label',
    'type' => 'text',
    'icon' => 'user',
    'value' => null,
    'placeholder' => null,
    'checklist' => false,   // show the live password-requirements checklist
    'matches' => null,      // id of the password field this one must match (confirmation)
])

@php
    $id = $attributes->get('id', $name);
    $isPassword = $type === 'password';
@endphp

<div class="field @error($name) has-error @enderror">
    <label for="{{ $id }}">{{ $label }}</label>
    <div class="auth-input">
        @switch($icon)
            @case('lock')
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>
                @break
            @case('mail')
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg>
                @break
            @case('at')
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="4"/><path d="M16 8v5a3 3 0 0 0 6 0v-1a10 10 0 1 0-4 8"/></svg>
                @break
            @default
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg>
        @endswitch

        <input id="{{ $id }}" name="{{ $name }}" type="{{ $type }}"
               @if(! $isPassword) value="{{ old($name, $value) }}" @endif
               placeholder="{{ $placeholder ?? $label }}"
               @if($checklist) data-password-rules="{{ $id }}-rules" @endif
               @if($matches) data-matches="{{ $matches }}" @endif
               @error($name) aria-invalid="true" @enderror
               {{ $attributes->except('id') }}>

        @if($isPassword)
            <button type="button" class="toggle-password" data-target="{{ $id }}">Show</button>
        @endif
    </div>

    <div class="field-required">This field is required.</div>

    @if($checklist)
        @include('partials.password-rules', ['id' => $id.'-rules'])
    @endif

    @if($matches)
        <div class="pw-match" id="{{ $id }}-match" aria-live="polite"></div>
    @endif

    @error($name)
        <div class="field-error">{{ $message }}</div>
    @enderror
</div>
