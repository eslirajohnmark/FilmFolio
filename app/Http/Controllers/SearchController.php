<?php

namespace App\Http\Controllers;

use App\Models\Film;
use App\Models\FilmList;
use App\Models\User;
use App\Services\Recommendations;
use App\Services\TmdbService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

class SearchController extends Controller
{
    public function index(Request $request, TmdbService $tmdb, Recommendations $recommendations): View
    {
        $q = trim((string) $request->query('q', ''));

        if (mb_strlen($q) < 2) {
            return view('search.index', [
                'q' => $q,
                'films' => collect(),
                'members' => collect(),
                'lists' => collect(),
                'tmdbResults' => collect(),
                'suggestions' => $recommendations->suggestionsFor(),
            ]);
        }

        $like = "%{$q}%";
        $films = Film::where('title', 'like', $like)->orderByDesc('year')->take(24)->get();

        $recommendations->recordSearch($q, $films);

        return view('search.index', [
            'q' => $q,
            'films' => $films,
            'suggestions' => $recommendations->suggestionsFor(),
            'members' => User::where(fn ($query) => $query->where('username', 'like', $like)->orWhere('name', 'like', $like))
                ->take(8)
                ->get(),
            'lists' => FilmList::public()
                ->with(['user', 'films' => fn ($query) => $query->limit(5)])
                ->withCount('films')->where('name', 'like', $like)->take(6)->get(),
            'tmdbResults' => $this->tmdbResults($tmdb, $q, $films->pluck('tmdb_id')->filter()),
        ]);
    }

    /**
     * Import a film from TMDB (if it isn't already in FilmFolio) and open its page.
     */
    public function importFromTmdb(int $tmdbId, TmdbService $tmdb): RedirectResponse
    {
        if ($film = Film::where('tmdb_id', $tmdbId)->first()) {
            return redirect()->route('films.show', $film);
        }

        try {
            $film = $tmdb->importFilm($tmdbId);
        } catch (Throwable $e) {
            Log::warning("TMDB import of #{$tmdbId} failed: {$e->getMessage()}");

            return back()->with('status', 'Could not load that film from TMDB right now. Please try again.');
        }

        return redirect()->route('films.show', $film);
    }

    /**
     * TMDB matches that aren't in the local catalogue yet.
     *
     * @param  Collection<int, int>  $knownIds
     * @return Collection<int, array{id: int, title: string, year: ?string, poster: ?string}>
     */
    protected function tmdbResults(TmdbService $tmdb, string $q, Collection $knownIds): Collection
    {
        if (! config('services.tmdb.key')) {
            return collect();
        }

        try {
            $results = Cache::remember('tmdb:search:'.md5(mb_strtolower($q)), now()->addHour(), fn () => $tmdb->search($q)['results'] ?? []);
        } catch (Throwable $e) {
            Log::warning("TMDB search failed: {$e->getMessage()}");

            return collect();
        }

        return collect($results)
            ->reject(fn ($movie) => $knownIds->contains($movie['id']))
            ->take(18)
            ->map(fn ($movie) => [
                'id' => (int) $movie['id'],
                'title' => $movie['title'] ?? 'Untitled',
                'year' => ($movie['release_date'] ?? '') ? substr($movie['release_date'], 0, 4) : null,
                'poster' => ($movie['poster_path'] ?? null) ? config('services.tmdb.image_base').$movie['poster_path'] : null,
            ])
            ->values();
    }
}
