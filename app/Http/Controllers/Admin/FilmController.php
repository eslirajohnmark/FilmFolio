<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\FilmRequest;
use App\Models\Film;
use App\Services\TmdbService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Throwable;

class FilmController extends Controller
{
    public function index(Request $request, TmdbService $tmdb): View
    {
        $q = trim((string) $request->query('q', ''));
        $filter = $request->query('filter', 'all');
        // From the dashboard's "Popular genres / decades" bars
        $genre = trim((string) $request->query('genre', ''));
        $decade = $request->integer('decade') ?: null;

        $films = Film::query()
            ->withCount(['reviews', 'diaryEntries'])
            ->when($q !== '', fn ($query) => $query->where('title', 'like', "%{$q}%"))
            ->when($genre !== '', fn ($query) => $query->where('genre', $genre))
            ->when($decade, fn ($query) => $query->whereBetween('year', [$decade, $decade + 9]))
            ->when($filter === 'teasers', fn ($query) => $query->where('is_teaser', true))
            ->when($filter === 'no-trailer', fn ($query) => $query->whereNull('trailer_key')->whereNull('trailer_path')->whereNull('teaser_path'))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $tmdbResults = collect();
        if ($request->filled('tmdb') && config('services.tmdb.key')) {
            try {
                $known = Film::whereNotNull('tmdb_id')->pluck('tmdb_id');
                $tmdbResults = collect($tmdb->search((string) $request->query('tmdb'))['results'] ?? [])
                    ->reject(fn ($movie) => $known->contains($movie['id']))
                    ->take(12);
            } catch (Throwable $e) {
                Log::warning("Admin TMDB search failed: {$e->getMessage()}");
                session()->now('status', 'TMDB is unavailable right now.');
            }
        }

        return view('admin.films.index', compact('films', 'q', 'filter', 'genre', 'decade', 'tmdbResults'));
    }

    public function create(): View
    {
        return view('admin.films.form', ['film' => new Film(['year' => now()->year]), 'genres' => $this->genres()]);
    }

    public function store(FilmRequest $request): RedirectResponse
    {
        $film = $this->save($request, new Film);

        return redirect()->route('admin.films.edit', $film)->with('status', "\"{$film->title}\" added.");
    }

    public function edit(Film $film): View
    {
        return view('admin.films.form', ['film' => $film, 'genres' => $this->genres()]);
    }

    /**
     * @return Collection<int, string>
     */
    protected function genres(): Collection
    {
        return Film::whereNotNull('genre')->distinct()->orderBy('genre')->pluck('genre');
    }

    public function update(FilmRequest $request, Film $film): RedirectResponse
    {
        $this->save($request, $film);

        return redirect()->route('admin.films.edit', $film)->with('status', 'Film updated.');
    }

    /**
     * Upload slots: column => [file input, "remove" checkbox, folder under films/].
     *
     * @var array<string, array{0: string, 1: string, 2: string}>
     */
    protected const UPLOADS = [
        'poster_path' => ['poster_file', 'remove_poster', 'posters'],
        'backdrop_path' => ['backdrop_file', 'remove_backdrop', 'backdrops'],
        'trailer_path' => ['trailer_file', 'remove_trailer_video', 'trailers'],
        'teaser_path' => ['teaser_file', 'remove_teaser_video', 'teasers'],
    ];

    /**
     * Save the film's fields and uploaded media. A new upload replaces the old
     * file; "remove" clears it; for poster/backdrop, a typed URL or TMDB path
     * replaces an upload, while leaving the box empty keeps the uploaded file.
     */
    protected function save(FilmRequest $request, Film $film): Film
    {
        $data = $request->filmData();
        $obsolete = [];

        foreach (self::UPLOADS as $column => [$fileField, $removeField, $folder]) {
            $current = $film->getOriginal($column);
            $typed = array_key_exists($column, $data) ? $data[$column] : null;

            $new = match (true) {
                $request->hasFile($fileField) => $request->file($fileField)->store(Film::UPLOAD_DIR.'/'.$folder, 'public'),
                $request->boolean($removeField) => null,
                filled($typed) => $typed,
                default => Film::isUpload($current) ? $current : null,
            };

            if ($current !== $new && Film::isUpload($current)) {
                $obsolete[] = $current;
            }

            $data[$column] = $new;
        }

        $film->fill($data)->save();

        Storage::disk('public')->delete($obsolete);

        return $film;
    }

    public function destroy(Film $film): RedirectResponse
    {
        $film->delete();

        return redirect()->route('admin.films.index')->with('status', "\"{$film->title}\" deleted.");
    }

    public function toggleTeaser(Film $film): RedirectResponse
    {
        $film->update(['is_teaser' => ! $film->is_teaser]);

        return back()->with('status', $film->is_teaser ? "\"{$film->title}\" is now a teaser." : "\"{$film->title}\" removed from teasers.");
    }

    public function import(Request $request, TmdbService $tmdb): RedirectResponse
    {
        $data = $request->validate([
            'tmdb_id' => ['required', 'integer', 'min:1'],
            'is_teaser' => ['boolean'],
        ]);

        try {
            $film = $tmdb->importFilm((int) $data['tmdb_id']);
        } catch (Throwable $e) {
            Log::warning("Admin TMDB import failed: {$e->getMessage()}");

            return back()->with('status', 'Could not import that film from TMDB.');
        }

        if ($request->boolean('is_teaser')) {
            $film->update(['is_teaser' => true]);
        }

        return redirect()->route('admin.films.edit', $film)->with('status', "\"{$film->title}\" imported from TMDB.");
    }
}
