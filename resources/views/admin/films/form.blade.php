@extends('layouts.admin')

@section('title', $film->exists ? 'Edit '.$film->title : 'Add film')

@section('content')
@php
    $isUpload = fn (?string $path) => \App\Models\Film::isUpload($path);
    $maxVideoMb = intdiv(\App\Http\Requests\Admin\FilmRequest::MAX_VIDEO_KB, 1024);
@endphp

<a href="{{ route('admin.films.index') }}" class="link" style="font-size:13px;">← All films</a>
<div class="admin-toolbar" style="margin-top:8px;">
    <h1 class="section-title" style="margin:0;">{{ $film->exists ? 'Edit “'.$film->title.'”' : 'Add a film' }}</h1>
    @if($film->exists)
        <a href="{{ route('films.show', $film) }}" class="link" style="font-size:13px;">View on site ↗</a>
    @endif
</div>

<form method="POST" enctype="multipart/form-data"
      action="{{ $film->exists ? route('admin.films.update', $film) : route('admin.films.store') }}"
      class="panel-form" id="filmForm">
    @csrf
    @if($film->exists) @method('PUT') @endif

    @if($errors->any())
        <div class="error-list">Please fix the highlighted fields.</div>
    @endif

    {{-- ---------- Details ---------- --}}
    <section class="panel" style="margin-top:0;">
        <h2>Details</h2>

        <div class="form-grid-2">
            <div class="field">
                <label for="title">Title</label>
                <input id="title" name="title" type="text" value="{{ old('title', $film->title) }}" required>
                @error('title') <div class="field-error">{{ $message }}</div> @enderror
            </div>
            <div class="field">
                <label for="year">Year</label>
                <input id="year" name="year" type="number" value="{{ old('year', $film->year) }}" required>
                @error('year') <div class="field-error">{{ $message }}</div> @enderror
            </div>
            <div class="field">
                <label for="genre">Genre</label>
                <input id="genre" name="genre" type="text" value="{{ old('genre', $film->genre) }}" list="genreOptions">
                <datalist id="genreOptions">
                    @foreach($genres as $genre)
                        <option value="{{ $genre }}">
                    @endforeach
                </datalist>
                @error('genre') <div class="field-error">{{ $message }}</div> @enderror
            </div>
            <div class="field">
                <label for="release_date">Release date</label>
                <input id="release_date" name="release_date" type="date" value="{{ old('release_date', $film->release_date?->format('Y-m-d')) }}">
                @error('release_date') <div class="field-error">{{ $message }}</div> @enderror
            </div>
        </div>

        <div class="field">
            <label for="synopsis">Description</label>
            <textarea id="synopsis" name="synopsis">{{ old('synopsis', $film->synopsis) }}</textarea>
            @error('synopsis') <div class="field-error">{{ $message }}</div> @enderror
        </div>

        <div class="form-grid-2">
            <div class="field">
                <label for="tmdb_id">TMDB id <span class="text-muted">(optional)</span></label>
                <input id="tmdb_id" name="tmdb_id" type="number" value="{{ old('tmdb_id', $film->tmdb_id) }}">
                @error('tmdb_id') <div class="field-error">{{ $message }}</div> @enderror
            </div>
            <div class="field" style="align-self:end;">
                <input type="hidden" name="is_teaser" value="0">
                <label class="checkbox"><input type="checkbox" name="is_teaser" value="1" @checked(old('is_teaser', $film->is_teaser))> Show on the Teasers page (upcoming film)</label>
            </div>
        </div>
    </section>

    {{-- ---------- Media ---------- --}}
    <section class="panel">
        <h2>Media</h2>
        <p class="panel-sub">Upload files from your computer, or paste a link instead.</p>

        <div class="media-grid">
            {{-- Poster --}}
            <div class="media-card">
                <h3>Poster</h3>
                <div class="media-preview poster-shape">
                    <img data-preview-for="poster_file" src="{{ $film->posterUrl() }}" alt="" @unless($film->posterUrl()) hidden @endunless>
                    <span class="media-empty" @if($film->posterUrl()) hidden @endif>No poster</span>
                </div>
                <label class="upload-btn">
                    <input type="file" name="poster_file" accept="image/jpeg,image/png,image/webp" data-preview>
                    📁 Upload image
                </label>
                <div class="hint">JPG, PNG or WebP · up to 5 MB · 2:3 works best</div>
                @error('poster_file') <div class="field-error">{{ $message }}</div> @enderror

                <label for="poster_path" class="or-label">or image URL / TMDB path</label>
                <input id="poster_path" name="poster_path" type="text"
                       value="{{ old('poster_path', $isUpload($film->poster_path) ? '' : $film->poster_path) }}"
                       placeholder="{{ $isUpload($film->poster_path) ? 'Using uploaded file' : 'https://… or /abc.jpg' }}">
                @error('poster_path') <div class="field-error">{{ $message }}</div> @enderror

                @if($film->poster_path)
                    <label class="checkbox remove"><input type="checkbox" name="remove_poster" value="1"> Remove poster</label>
                @endif
            </div>

            {{-- Backdrop --}}
            <div class="media-card">
                <h3>Backdrop</h3>
                <div class="media-preview wide-shape">
                    <img data-preview-for="backdrop_file" src="{{ $film->backdropUrl() }}" alt="" @unless($film->backdropUrl()) hidden @endunless>
                    <span class="media-empty" @if($film->backdropUrl()) hidden @endif>No backdrop</span>
                </div>
                <label class="upload-btn">
                    <input type="file" name="backdrop_file" accept="image/jpeg,image/png,image/webp" data-preview>
                    📁 Upload image
                </label>
                <div class="hint">Wide image shown behind the film page · up to 8 MB</div>
                @error('backdrop_file') <div class="field-error">{{ $message }}</div> @enderror

                <label for="backdrop_path" class="or-label">or image URL / TMDB path</label>
                <input id="backdrop_path" name="backdrop_path" type="text"
                       value="{{ old('backdrop_path', $isUpload($film->backdrop_path) ? '' : $film->backdrop_path) }}"
                       placeholder="{{ $isUpload($film->backdrop_path) ? 'Using uploaded file' : 'https://… or /abc.jpg' }}">
                @error('backdrop_path') <div class="field-error">{{ $message }}</div> @enderror

                @if($film->backdrop_path)
                    <label class="checkbox remove"><input type="checkbox" name="remove_backdrop" value="1"> Remove backdrop</label>
                @endif
            </div>

            {{-- Trailer --}}
            <div class="media-card">
                <h3>Trailer</h3>
                <div class="media-preview wide-shape">
                    <video data-preview-for="trailer_file" src="{{ $film->trailerVideoUrl() }}" controls preload="metadata" @unless($film->trailerVideoUrl()) hidden @endunless></video>
                    @if(! $film->trailerVideoUrl() && $film->trailerEmbedUrl())
                        <iframe src="{{ $film->trailerEmbedUrl() }}" title="YouTube trailer" loading="lazy" allowfullscreen data-youtube></iframe>
                    @endif
                    <span class="media-empty" @if($film->trailerVideoUrl() || $film->trailerEmbedUrl()) hidden @endif>No trailer</span>
                </div>
                <label class="upload-btn">
                    <input type="file" name="trailer_file" accept="video/mp4,video/webm,video/quicktime,video/ogg" data-preview>
                    🎬 Upload video
                </label>
                <div class="hint">MP4, WebM, MOV or OGG · up to {{ $maxVideoMb }} MB · shown on the film page</div>
                @error('trailer_file') <div class="field-error">{{ $message }}</div> @enderror

                <label for="trailer" class="or-label">or YouTube link</label>
                <input id="trailer" name="trailer" type="text" value="{{ old('trailer', $film->trailerUrl()) }}" placeholder="https://www.youtube.com/watch?v=…">
                @error('trailer') <div class="field-error">{{ $message }}</div> @enderror

                @if($film->trailer_path)
                    <label class="checkbox remove"><input type="checkbox" name="remove_trailer_video" value="1"> Remove uploaded trailer video</label>
                @endif
            </div>

            {{-- Teaser --}}
            <div class="media-card">
                <h3>Teaser video</h3>
                <div class="media-preview wide-shape">
                    <video data-preview-for="teaser_file" src="{{ $film->teaserVideoUrl() }}" controls preload="metadata" @unless($film->teaserVideoUrl()) hidden @endunless></video>
                    <span class="media-empty" @if($film->teaserVideoUrl()) hidden @endif>No teaser video</span>
                </div>
                <label class="upload-btn">
                    <input type="file" name="teaser_file" accept="video/mp4,video/webm,video/quicktime,video/ogg" data-preview>
                    🎬 Upload video
                </label>
                <div class="hint">Short clip for the Teasers page (falls back to the trailer) · up to {{ $maxVideoMb }} MB</div>
                @error('teaser_file') <div class="field-error">{{ $message }}</div> @enderror

                @if($film->teaser_path)
                    <label class="checkbox remove"><input type="checkbox" name="remove_teaser_video" value="1"> Remove teaser video</label>
                @endif
            </div>
        </div>
    </section>

    <div class="actions" style="margin-top:16px;">
        <button type="submit" class="btn btn-primary" id="filmSubmit">{{ $film->exists ? 'Save changes' : 'Add film' }}</button>
        <span class="upload-status" id="uploadStatus" hidden>Uploading… large videos can take a minute.</span>
    </div>
</form>

<script>
    // Show the chosen image/video immediately, before saving.
    document.querySelectorAll('#filmForm input[type=file][data-preview]').forEach((input) => {
        input.addEventListener('change', () => {
            const file = input.files[0];
            const card = input.closest('.media-card');
            const preview = card.querySelector(`[data-preview-for="${input.name}"]`);
            if (! file || ! preview) return;

            preview.src = URL.createObjectURL(file);
            preview.hidden = false;
            card.querySelector('.media-empty')?.setAttribute('hidden', '');
            card.querySelector('[data-youtube]')?.remove();
            input.closest('.upload-btn').lastChild.textContent = ' ' + file.name;
        });
    });

    document.getElementById('filmForm').addEventListener('submit', () => {
        const hasFiles = [...document.querySelectorAll('#filmForm input[type=file]')].some((i) => i.files.length);
        if (hasFiles) {
            document.getElementById('uploadStatus').hidden = false;
            document.getElementById('filmSubmit').disabled = true;
        }
    });
</script>
@endsection
