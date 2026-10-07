<?php

namespace App\Console\Commands;

use App\Models\Film;
use App\Services\TmdbService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

#[Signature('films:sync {--trending=20 : How many trending films to import} {--upcoming=6 : How many upcoming films to import as teasers}')]
#[Description('Import trending and upcoming films (with trailers) from TMDB')]
class SyncFilms extends Command
{
    public function handle(TmdbService $tmdb): int
    {
        if (! config('services.tmdb.key')) {
            $this->error('TMDB_API_KEY is not set.');

            return self::FAILURE;
        }

        $failures = 0;

        try {
            $trending = array_slice($tmdb->trending()['results'] ?? [], 0, (int) $this->option('trending'));
            $upcoming = array_slice($tmdb->upcoming()['results'] ?? [], 0, (int) $this->option('upcoming'));
        } catch (Throwable $e) {
            $this->error('Could not reach TMDB: '.$e->getMessage());

            return self::FAILURE;
        }

        foreach ($trending as $movie) {
            $failures += $this->import($tmdb, $movie['id']) ? 0 : 1;
        }

        Film::where('is_teaser', true)->update(['is_teaser' => false]);

        foreach ($upcoming as $movie) {
            $film = $this->import($tmdb, $movie['id']);
            $film?->update(['is_teaser' => true]);
            $failures += $film ? 0 : 1;
        }

        $this->info(sprintf(
            'Synced %d trending and %d upcoming films (%d failed).',
            count($trending), count($upcoming), $failures,
        ));

        return $failures === 0 ? self::SUCCESS : self::FAILURE;
    }

    protected function import(TmdbService $tmdb, int $tmdbId): ?Film
    {
        try {
            return $tmdb->importFilm($tmdbId);
        } catch (Throwable $e) {
            $this->warn("Skipped TMDB #{$tmdbId}: {$e->getMessage()}");

            return null;
        }
    }
}
