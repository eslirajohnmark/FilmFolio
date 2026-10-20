<div>
    <form class="filter-bar" wire:submit.prevent>
        <label class="filter-search">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
            <input type="search" placeholder="Search films by title, genre or year…"
                   wire:model.live.debounce.300ms="q" aria-label="Search films">
        </label>

        <select wire:model.live="genre" aria-label="Genre">
            <option value="">All Genres</option>
            @foreach($genres as $g)
                <option value="{{ $g }}">{{ $g }}</option>
            @endforeach
        </select>

        <select wire:model.live="decade" aria-label="Decade">
            <option value="">All Decades</option>
            @foreach($decades as $d)
                <option value="{{ $d }}">{{ $d }}s</option>
            @endforeach
        </select>

        <select wire:model.live="sort" aria-label="Sort by">
            @foreach($sorts as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </select>
    </form>

    <div class="poster-grid" wire:loading.class="is-loading">
        @forelse($films as $film)
            <div wire:key="film-{{ $film->id }}">
                @include('partials.poster-card', ['film' => $film])
            </div>
        @empty
            <p class="empty-state" style="grid-column:1 / -1;">
                No films match those filters.
                @if(mb_strlen($q) >= 2)
                    Try <a href="{{ route('search', ['q' => $q]) }}">searching all of TMDB</a>.
                @endif
            </p>
        @endforelse
    </div>

    {{ $films->links() }}
</div>
