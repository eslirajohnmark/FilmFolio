{{-- $mark 'letter' (navbar "F" tile, default) or 'film' (film-strip icon, auth cards) --}}
<span class="brand-logo" aria-hidden="true">
    @if(($mark ?? 'letter') === 'film')
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="3" width="18" height="18" rx="2"/>
            <path d="M7 3v18M17 3v18M3 7.5h4M3 12h18M3 16.5h4M17 7.5h4M17 16.5h4"/>
        </svg>
    @else
        <span class="brand-letter">F</span>
    @endif
</span>
<span class="brand-text">FilmFolio</span>
