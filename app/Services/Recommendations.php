<?php

namespace App\Services;

use App\Models\Film;
use App\Models\InterestSignal;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * "Suggested for you": works out which genres someone likes and suggests films in them.
 *
 * Interest comes from two places:
 *  - signals: what they searched for (searching "horror" or "scary" counts towards Horror),
 *    films they opened, and genre filters they used — for guests too, by session;
 *  - for members, what they did: high and low ratings, likes, watchlist and watched films.
 * Recent signals count more than old ones.
 */
class Recommendations
{
    /**
     * Signal weights.
     */
    public const SEARCH_GENRE = 4;

    public const SEARCH_TITLE = 1;

    public const FILTER = 2;

    public const VIEW = 1;

    /**
     * Signals older than this are ignored; their weight halves every HALF_LIFE_DAYS.
     */
    public const WINDOW_DAYS = 90;

    public const HALF_LIFE_DAYS = 14;

    /**
     * The same signal isn't recorded twice within this many minutes (typing, refreshes).
     */
    public const DEDUPE_MINUTES = 10;

    /**
     * Everyday words that point at a genre, matched against the genres FilmFolio has.
     *
     * @var array<string, list<string>>
     */
    public const SYNONYMS = [
        'Horror' => ['horror', 'scary', 'spooky', 'creepy', 'ghost', 'zombie', 'haunted', 'slasher', 'terror'],
        'Comedy' => ['comedy', 'funny', 'hilarious', 'laugh', 'comedic'],
        'Science Fiction' => ['sci-fi', 'scifi', 'sci fi', 'science fiction', 'space', 'alien', 'robot', 'futuristic'],
        'Animation' => ['animation', 'animated', 'cartoon', 'anime', 'pixar'],
        'Romance' => ['romance', 'romantic', 'love story', 'rom-com', 'romcom'],
        'Action' => ['action', 'fight', 'explosive', 'martial arts'],
        'Thriller' => ['thriller', 'suspense', 'tense'],
        'Adventure' => ['adventure', 'quest', 'journey'],
        'Drama' => ['drama', 'emotional', 'tearjerker'],
        'Mystery' => ['mystery', 'whodunit', 'detective'],
        'Crime' => ['crime', 'heist', 'gangster', 'mafia'],
        'Fantasy' => ['fantasy', 'magic', 'dragon', 'wizard'],
        'History' => ['history', 'historical', 'period'],
        'War' => ['war', 'battle', 'soldier'],
        'Family' => ['family', 'kids', 'children'],
        'Documentary' => ['documentary', 'docu', 'true story'],
        'Music' => ['music', 'musical', 'concert'],
        'Western' => ['western', 'cowboy'],
    ];

    /**
     * Record a search: genres the words point to count strongly, the genres of
     * matching titles a little.
     *
     * @param  Collection<int, Film>|null  $matchingFilms
     */
    public function recordSearch(string $query, ?Collection $matchingFilms = null): void
    {
        $query = Str::of($query)->squish()->lower()->limit(100, '')->toString();

        if (mb_strlen($query) < 3) {
            return;
        }

        foreach ($this->genresMentionedIn($query) as $genre) {
            $this->record($genre, InterestSignal::SOURCE_SEARCH, self::SEARCH_GENRE, $query);
        }

        ($matchingFilms ?? collect())
            ->pluck('genre')->filter()->unique()->take(3)
            ->each(fn (string $genre) => $this->record($genre, InterestSignal::SOURCE_SEARCH, self::SEARCH_TITLE, $query));
    }

    public function recordView(Film $film): void
    {
        if ($film->genre) {
            $this->record($film->genre, InterestSignal::SOURCE_VIEW, self::VIEW);
        }
    }

    public function recordFilter(string $genre): void
    {
        if (in_array($genre, $this->knownGenres(), true)) {
            $this->record($genre, InterestSignal::SOURCE_FILTER, self::FILTER);
        }
    }

    /**
     * Genres FilmFolio has that a search mentions, by name or by everyday word.
     *
     * @return list<string>
     */
    public function genresMentionedIn(string $query): array
    {
        $query = ' '.Str::lower($query).' ';

        return collect($this->knownGenres())
            ->filter(function (string $genre) use ($query): bool {
                $words = array_merge([Str::lower($genre)], self::SYNONYMS[$genre] ?? []);

                foreach ($words as $word) {
                    if (preg_match('/(?<![a-z])'.preg_quote($word, '/').'/', $query)) {
                        return true;
                    }
                }

                // Still typing: "horr" → Horror
                $typed = trim($query);

                return mb_strlen($typed) >= 4 && ! str_contains($typed, ' ') && Str::startsWith(Str::lower($genre), $typed);
            })
            ->values()
            ->all();
    }

    /**
     * How much the viewer likes each genre, highest first. Only positive scores.
     *
     * @return Collection<string, float>
     */
    public function genreScores(?User $user = null, ?string $sessionKey = null): Collection
    {
        [$user, $sessionKey] = $this->owner($user, $sessionKey);
        $scores = collect();

        if (! $user && ! $sessionKey) {
            return $scores;
        }

        InterestSignal::ownedBy($user, $sessionKey)
            ->where('created_at', '>=', now()->subDays(self::WINDOW_DAYS))
            ->get(['genre', 'weight', 'created_at'])
            ->each(function (InterestSignal $signal) use ($scores): void {
                $ageInDays = $signal->created_at->diffInSeconds(now()) / 86400;
                $scores[$signal->genre] = ($scores[$signal->genre] ?? 0) + $signal->weight * 0.5 ** ($ageInDays / self::HALF_LIFE_DAYS);
            });

        if ($user) {
            foreach ($this->behaviourScores($user) as $genre => $score) {
                $scores[$genre] = ($scores[$genre] ?? 0) + $score;
            }
        }

        return $scores->filter(fn (float $score) => $score > 0.25)->sortDesc();
    }

    /**
     * Films to suggest, and the genres they were picked from. Null when there's nothing to go on yet.
     *
     * @return array{films: Collection<int, Film>, genres: list<string>}|null
     */
    public function suggestionsFor(?User $user = null, ?string $sessionKey = null, int $limit = 6): ?array
    {
        [$user, $sessionKey] = $this->owner($user, $sessionKey);
        $scores = $this->genreScores($user, $sessionKey);

        if ($scores->isEmpty()) {
            return null;
        }

        $topGenres = $scores->keys()->take(3)->all();
        $seen = $user ? $this->filmsAlreadySeen($user) : collect();

        $films = Film::query()
            ->whereIn('genre', $topGenres)
            ->whereNotIn('id', $seen)
            ->withCardStats()
            ->get()
            ->sortByDesc(fn (Film $film) => [
                $scores[$film->genre] ?? 0,
                (float) $film->reviews_avg_rating,
                $film->watchers_count,
            ])
            ->take($limit)
            ->values();

        if ($films->isEmpty()) {
            return null;
        }

        return [
            'films' => $films,
            'genres' => $topGenres,
        ];
    }

    /**
     * A guest's signals become theirs when they sign in or register.
     */
    public function claimGuestSignals(User $user, string $sessionKey): void
    {
        InterestSignal::whereNull('user_id')
            ->where('session_key', $sessionKey)
            ->update(['user_id' => $user->id, 'session_key' => null]);
    }

    protected function record(string $genre, string $source, int $weight, ?string $term = null): void
    {
        // A page refreshing itself in place isn't the member viewing or searching again.
        if (LiveUpdates::isRefresh()) {
            return;
        }

        [$user, $sessionKey] = $this->owner();

        if (! $user && ! $sessionKey) {
            return;
        }

        $recent = InterestSignal::ownedBy($user, $sessionKey)
            ->where('genre', $genre)
            ->where('source', $source)
            ->where('weight', $weight)
            ->where('created_at', '>=', now()->subMinutes(self::DEDUPE_MINUTES))
            ->latest()
            ->first();

        // Typing "horr", "horro", "horror" is one search: keep a single signal with the fullest term.
        if ($recent) {
            if ($term !== null && ($recent->term === null || ! Str::startsWith($recent->term, $term))) {
                $recent->forceFill(['term' => $term])->save();
            }

            return;
        }

        InterestSignal::create([
            'user_id' => $user?->id,
            'session_key' => $user ? null : $sessionKey,
            'genre' => $genre,
            'source' => $source,
            'term' => $term,
            'weight' => $weight,
        ]);
    }

    /**
     * Genre scores from what a member has done: ratings (low ratings count against
     * a genre), likes, watchlist and watched films.
     *
     * @return array<string, float>
     */
    protected function behaviourScores(User $user): array
    {
        $scores = [];
        $add = function (iterable $rows, callable $points) use (&$scores): void {
            foreach ($rows as $row) {
                $scores[$row->genre] = ($scores[$row->genre] ?? 0) + $points($row);
            }
        };

        $add(
            DB::table('reviews')->join('films', 'films.id', '=', 'reviews.film_id')
                ->where('reviews.user_id', $user->id)->whereNotNull('films.genre')
                ->get(['films.genre', 'reviews.rating']),
            fn ($row) => match (true) {
                $row->rating >= 4 => 3,
                $row->rating >= 3 => 1,
                $row->rating <= 2 => -2,
                default => 0,
            },
        );

        $add(
            DB::table('film_user')->join('films', 'films.id', '=', 'film_user.film_id')
                ->where('film_user.user_id', $user->id)->whereNotNull('films.genre')
                ->get(['films.genre', 'film_user.liked', 'film_user.watched']),
            fn ($row) => ($row->liked ? 2 : 0) + ($row->watched ? 1 : 0),
        );

        $add(
            DB::table('watchlists')->join('films', 'films.id', '=', 'watchlists.film_id')
                ->where('watchlists.user_id', $user->id)->whereNotNull('films.genre')
                ->get(['films.genre']),
            fn () => 2,
        );

        return $scores;
    }

    /**
     * Films a member has watched or rated, which aren't worth suggesting again.
     *
     * @return Collection<int, int>
     */
    protected function filmsAlreadySeen(User $user): Collection
    {
        return DB::table('film_user')->where('user_id', $user->id)->where('watched', true)->pluck('film_id')
            ->merge(DB::table('reviews')->where('user_id', $user->id)->pluck('film_id'))
            ->unique()
            ->values();
    }

    /**
     * @return list<string>
     */
    protected function knownGenres(): array
    {
        return Cache::remember('films:genres', now()->addMinutes(10), fn () => Film::query()
            ->whereNotNull('genre')->distinct()->orderBy('genre')->pluck('genre')->all());
    }

    /**
     * The member, or the guest's session id.
     *
     * @return array{0: ?User, 1: ?string}
     */
    protected function owner(?User $user = null, ?string $sessionKey = null): array
    {
        $user ??= auth()->user();

        if (! $user && ! $sessionKey && app()->bound('session') && request()->hasSession()) {
            $sessionKey = request()->session()->getId();
        }

        return [$user, $sessionKey];
    }
}
