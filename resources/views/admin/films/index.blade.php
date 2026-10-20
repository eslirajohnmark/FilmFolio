@extends('layouts.admin')

@section('title', 'Films')

@section('content')
<div class="admin-toolbar">
    <div>
        <h1 class="section-title" style="margin:0;">Films</h1>
        <p class="panel-sub" style="margin:4px 0 0;">Add, edit and manage films and teasers.</p>
    </div>
    <a href="{{ route('admin.films.create') }}" class="btn btn-primary">+ Add film</a>
</div>

<div class="admin-grid-2" style="gap:0 24px;">
    <form method="GET" action="{{ route('admin.films.index') }}" class="search-bar" style="margin-bottom:12px;">
        <input type="search" name="q" value="{{ $q }}" placeholder="Search catalogue…" aria-label="Search films">
        <input type="hidden" name="filter" value="{{ $filter }}">
        @if($genre)<input type="hidden" name="genre" value="{{ $genre }}">@endif
        @if($decade)<input type="hidden" name="decade" value="{{ $decade }}">@endif
        <button class="btn btn-secondary">Search</button>
    </form>

    <form method="GET" action="{{ route('admin.films.index') }}" class="search-bar" style="margin-bottom:12px;">
        <input type="search" name="tmdb" value="{{ request('tmdb') }}" placeholder="Find a film on TMDB to import…" aria-label="Search TMDB">
        <button class="btn btn-secondary">Find on TMDB</button>
    </form>
</div>

@if($tmdbResults->isNotEmpty())
    <div class="section-label" style="margin-top:8px;">TMDB results for “{{ request('tmdb') }}”</div>
    <div class="table-wrap" style="margin-bottom:28px;">
        <table class="admin-table">
            <tbody>
                @foreach($tmdbResults as $movie)
                    <tr>
                        <td style="width:48px;">
                            @if($movie['poster_path'] ?? null)
                                <img src="{{ config('services.tmdb.image_base').$movie['poster_path'] }}" class="admin-thumb" alt="">
                            @else
                                <div class="admin-thumb"></div>
                            @endif
                        </td>
                        <td>
                            <b style="color:#fff;">{{ $movie['title'] ?? 'Untitled' }}</b>
                            <span class="text-muted">({{ ($movie['release_date'] ?? '') ? substr($movie['release_date'], 0, 4) : '—' }})</span>
                        </td>
                        <td style="text-align:right;">
                            <form method="POST" action="{{ route('admin.films.import') }}" class="row-actions" style="justify-content:flex-end;align-items:center;">
                                @csrf
                                <input type="hidden" name="tmdb_id" value="{{ $movie['id'] }}">
                                <label class="text-muted" style="font-size:12px;display:inline-flex;gap:4px;align-items:center;">
                                    <input type="checkbox" name="is_teaser" value="1"> as teaser
                                </label>
                                <button class="btn btn-primary" style="padding:5px 12px;">Import</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@elseif(request()->filled('tmdb'))
    <p class="empty-state">No new TMDB matches for “{{ request('tmdb') }}”.</p>
@endif

<div class="filter-tabs" style="margin-bottom:14px;">
    @foreach(['all' => 'All films', 'teasers' => 'Teasers', 'no-trailer' => 'No trailer'] as $key => $label)
        <a href="{{ route('admin.films.index', array_filter(['filter' => $key, 'q' => $q, 'genre' => $genre, 'decade' => $decade])) }}" class="{{ $filter === $key ? 'active' : '' }}">{{ $label }}</a>
    @endforeach
    {{-- Genre / decade picked from the dashboard: click to clear --}}
    @if($genre)
        <a href="{{ route('admin.films.index', array_filter(['filter' => $filter, 'q' => $q, 'decade' => $decade])) }}" class="active" title="Clear the genre filter">{{ $genre }} ✕</a>
    @endif
    @if($decade)
        <a href="{{ route('admin.films.index', array_filter(['filter' => $filter, 'q' => $q, 'genre' => $genre])) }}" class="active" title="Clear the decade filter">{{ $decade }}s ✕</a>
    @endif
</div>

<div class="table-wrap">
    <table class="admin-table">
        <thead>
            <tr>
                <th></th>
                <th>Title</th>
                <th>Genre</th>
                <th style="text-align:right;">Reviews</th>
                <th style="text-align:right;">Logs</th>
                <th>Teaser</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($films as $film)
                <tr>
                    <td style="width:48px;">
                        @if($film->posterUrl())
                            <img src="{{ $film->posterUrl() }}" class="admin-thumb" alt="">
                        @else
                            <div class="admin-thumb"></div>
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('films.show', $film) }}" style="color:#fff;font-weight:600;">{{ $film->title }}</a>
                        <span class="text-muted">({{ $film->year }})</span>
                        @unless($film->hasTrailer()) <span class="text-muted" style="font-size:11px;">· no trailer</span> @endunless
                    </td>
                    <td>{{ $film->genre ?? '—' }}</td>
                    <td style="text-align:right;">{{ number_format($film->reviews_count) }}</td>
                    <td style="text-align:right;">{{ number_format($film->diary_entries_count) }}</td>
                    <td>
                        <form method="POST" action="{{ route('admin.films.teaser', $film) }}">
                            @csrf
                            @method('PATCH')
                            <button class="status {{ $film->is_teaser ? 'status-active' : 'status-suspended' }}" style="border:none;cursor:pointer;"
                                    title="{{ $film->is_teaser ? 'Remove from teasers' : 'Make a teaser' }}">
                                {{ $film->is_teaser ? 'Teaser' : 'No' }}
                            </button>
                        </form>
                    </td>
                    <td>
                        <div class="row-actions">
                            <a href="{{ route('admin.films.edit', $film) }}" class="icon-btn icon-edit" title="Edit">✎</a>
                            <form method="POST" action="{{ route('admin.films.destroy', $film) }}"
                                  onsubmit="return confirm(@js('Delete “'.$film->title.'”? Its reviews, ratings and diary entries will be deleted too.'))">
                                @csrf
                                @method('DELETE')
                                <button class="icon-btn icon-delete" title="Delete">🗑</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="empty-state">No films found.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
{{ $films->links() }}
@endsection
