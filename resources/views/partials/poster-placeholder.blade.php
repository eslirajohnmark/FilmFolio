{{-- Shown inside an empty .poster. $icon 'play' (default) or 'film'. --}}
<span class="poster-placeholder" aria-hidden="true">
    @if(($icon ?? 'play') === 'film')
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M4 3h16a1 1 0 0 1 1 1v16a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1Zm1 2v2h2V5H5Zm12 0v2h2V5h-2ZM5 9v2h2V9H5Zm12 0v2h2V9h-2ZM5 13v2h2v-2H5Zm12 0v2h2v-2h-2ZM5 17v2h2v-2H5Zm12 0v2h2v-2h-2Z"/></svg>
    @else
        <span class="placeholder-play"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M8 5.5v13l11-6.5z"/></svg></span>
        <span class="placeholder-label">Poster</span>
    @endif
</span>
