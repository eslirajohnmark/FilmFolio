@props([
    'name',
    'label',
    'type' => 'text',
    'icon' => 'user',
    'value' => null,
    'placeholder' => null,
])

@php
    $id = $attributes->get('id', $name);
    $isPassword = $type === 'password';
@endphp

<div class="field">
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
               {{ $attributes->except('id') }}>

        @if($isPassword)
            <button type="button" class="toggle-password" data-target="{{ $id }}">Show</button>
        @endif
    </div>
    @error($name)
        <div class="field-error">{{ $message }}</div>
    @enderror
</div>
