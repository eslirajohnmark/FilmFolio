<?php

namespace App\Services;

use App\Models\Film;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Throwable;

class TmdbService
{
    public function trending(int $page = 1): array
    {
        return $this->get('/trending/movie/week', ['page' => $page]);
    }

    public function upcoming(int $page = 1): array
    {
        return $this->get('/movie/upcoming', ['page' => $page]);
    }

    public function search(string $query): array
    {
        return $this->get('/search/movie', ['query' => $query]);
    }

    public function details(int $tmdbId): array
    {
        return $this->get("/movie/{$tmdbId}", ['append_to_response' => 'videos']);
    }

    /**
     * Import a film by TMDB id, or return the existing record. Existing films
     * missing a trailer get one filled in.
     */
    public function importFilm(int $tmdbId): Film
    {
        $existing = Film::where('tmdb_id', $tmdbId)->first();

        if ($existing && $existing->trailer_key) {
            return $existing;
        }

        $d = $this->details($tmdbId);

        if ($existing) {
            $existing->update(['trailer_key' => $this->trailerKeyFrom($d)]);

            return $existing;
        }

        $releaseDate = ($d['release_date'] ?? '') ?: null;

        return Film::create([
            'tmdb_id' => $tmdbId,
            'title' => $d['title'] ?? 'Untitled',
            'year' => $releaseDate ? substr($releaseDate, 0, 4) : date('Y'),
            'release_date' => $releaseDate,
            'synopsis' => $d['overview'] ?? null,
            'poster_path' => $d['poster_path'] ?? null,
            'backdrop_path' => $d['backdrop_path'] ?? null,
            'trailer_key' => $this->trailerKeyFrom($d),
            'genre' => $d['genres'][0]['name'] ?? null,
        ]);
    }

    /**
     * Pick the YouTube key of the official trailer, falling back to any YouTube trailer or teaser.
     */
    protected function trailerKeyFrom(array $details): ?string
    {
        $videos = collect($details['videos']['results'] ?? [])
            ->where('site', 'YouTube')
            ->whereIn('type', ['Trailer', 'Teaser']);

        return ($videos->firstWhere('official', true) ?? $videos->first())['key'] ?? null;
    }

    /**
     * @throws RequestException|ConnectionException
     */
    protected function get(string $path, array $query = []): array
    {
        return Http::baseUrl(config('services.tmdb.base_url'))
            ->connectTimeout(3)
            ->timeout(10)
            ->retry([200, 1000], 0, fn (Throwable $e) => $e instanceof ConnectionException
                || ($e instanceof RequestException && ($e->response->serverError() || $e->response->status() === 429)))
            ->withQueryParameters($query + [
                'api_key' => config('services.tmdb.key'),
            ])
            ->get($path)
            ->throw()
            ->json() ?? [];
    }
}
