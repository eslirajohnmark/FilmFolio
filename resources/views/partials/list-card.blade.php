{{-- $list FilmList with films_count; `films` (first few, eager loaded) and `user` are optional. --}}
<div class="list-card">
    @can('update', $list)
        <div class="card-actions">
            <a href="{{ route('lists.show', $list) }}#edit" class="icon-btn icon-edit" title="Edit">✎</a>
            <form method="POST" action="{{ route('lists.destroy', $list) }}"
                  onsubmit="return confirm('Delete this list?')" style="display:inline;">
                @csrf
                @method('DELETE')
                <button class="icon-btn icon-delete" title="Delete">🗑</button>
            </form>
        </div>
    @elseif(auth('admin')->check())
        <div class="card-actions">
            <form method="POST" action="{{ route('admin.lists.destroy', $list) }}"
                  onsubmit="return confirm('Remove this list? (admin)')" style="display:inline;">
                @csrf
                @method('DELETE')
                <button class="icon-btn icon-delete" title="Remove (admin)">🗑</button>
            </form>
        </div>
    @endcan

    <a href="{{ route('lists.show', $list) }}" class="poster-stack" aria-hidden="true" tabindex="-1">
        @php $previews = $list->relationLoaded('films') ? $list->films->take(5) : collect(); @endphp
        @foreach($previews as $film)
            <div class="poster" @if($film->posterUrl()) style="background-image:url('{{ $film->posterUrl() }}')" @endif></div>
        @endforeach
        @for($i = $previews->count(); $i < 5; $i++)
            <div class="poster"></div>
        @endfor
    </a>

    <h3><a href="{{ route('lists.show', $list) }}">{{ $list->name }}</a></h3>
    <div class="meta">
        @if($list->relationLoaded('user'))
            <span class="owner">{{ $list->ownerName() }}</span>
        @endif
        <span>{{ $list->films_count }} {{ Str::plural('Film', $list->films_count) }}</span>
        @isset($list->likes_count)
            <span>♥ {{ $list->likes_count }}</span>
        @endisset
        @if($list->is_official)
            <span class="badge">Crew Pick</span>
        @elseif($list->is_featured)
            <span class="badge">Featured</span>
        @endif
        @if($list->visibility === 'private')
            <span class="badge red">Private</span>
        @endif
    </div>
</div>
