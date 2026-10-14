<?php

namespace App\Livewire;

use App\Models\Activity;
use App\Models\Film;
use App\Models\Review;
use App\Models\User;
use Livewire\Component;

/**
 * Letterboxd-style action panel on the film page: watched, liked, watchlist,
 * a quick half-star rating, and favourite films.
 */
class FilmActions extends Component
{
    public const MAX_FAVORITES = 4;

    public Film $film;

    public bool $watched = false;

    public bool $liked = false;

    public bool $inWatchlist = false;

    public bool $isFavorite = false;

    public float $rating = 0;

    public function mount(Film $film): void
    {
        $this->film = $film;
        $this->refreshState();
    }

    public function toggleWatched(): void
    {
        $user = $this->requireUser();
        if (! $user) {
            return;
        }

        if ($this->watched) {
            $user->films()->syncWithoutDetaching([$this->film->id => ['watched' => false]]);
        } else {
            $user->markWatched($this->film);
        }

        $this->refreshState();
    }

    public function toggleLiked(): void
    {
        $user = $this->requireUser();
        if (! $user) {
            return;
        }

        if ($this->liked) {
            $user->films()->syncWithoutDetaching([$this->film->id => ['liked' => false]]);
        } else {
            $user->markWatched($this->film);
            $user->films()->updateExistingPivot($this->film->id, ['liked' => true]);
            Activity::log($user, 'liked_film', $this->film);
        }

        $this->refreshState();
    }

    public function toggleWatchlist(): void
    {
        $user = $this->requireUser();
        if (! $user) {
            return;
        }

        if ($this->inWatchlist) {
            $user->watchlist()->detach($this->film->id);
        } else {
            $user->watchlist()->syncWithoutDetaching([$this->film->id]);
            Activity::log($user, 'watchlist_added', $this->film);
        }

        $this->refreshState();
    }

    public function rate(float $value): void
    {
        $user = $this->requireUser();
        if (! $user) {
            return;
        }

        if ($value < 0.5 || $value > 5 || fmod($value * 2, 1.0) !== 0.0) {
            return;
        }

        $review = Review::updateOrCreate(
            ['user_id' => $user->id, 'film_id' => $this->film->id],
            ['rating' => $value],
        );
        $user->markWatched($this->film);

        if ($review->wasRecentlyCreated || $review->wasChanged('rating')) {
            Activity::log($user, 'rated', $this->film, ['rating' => $value]);
        }

        $this->refreshState();
        $this->dispatch('notify', message: "Rated {$value}★");
    }

    public function clearRating(): void
    {
        $user = $this->requireUser();
        if (! $user) {
            return;
        }

        $review = Review::where('user_id', $user->id)->where('film_id', $this->film->id)->first();

        if ($review && ! $review->body) {
            $review->delete();
        } elseif ($review) {
            $this->dispatch('notify', message: 'Your review keeps its rating — edit the review to change it.', type: 'error');
        }

        $this->refreshState();
    }

    public function toggleFavorite(): void
    {
        $user = $this->requireUser();
        if (! $user) {
            return;
        }

        if ($this->isFavorite) {
            $user->favoriteFilms()->detach($this->film->id);
        } elseif ($user->favoriteFilms()->count() >= self::MAX_FAVORITES) {
            $this->dispatch('notify', message: 'You already have '.self::MAX_FAVORITES.' favourites. Remove one from your profile first.', type: 'error');

            return;
        } else {
            $user->favoriteFilms()->attach($this->film->id, [
                'position' => (int) $user->favoriteFilms()->max('favorite_films.position') + 1,
            ]);
        }

        $this->refreshState();
    }

    protected function requireUser(): ?User
    {
        if (! auth()->check()) {
            $this->redirect(route('login'));

            return null;
        }

        return auth()->user();
    }

    protected function refreshState(): void
    {
        $user = auth()->user();

        if (! $user) {
            return;
        }

        $state = $user->films()->whereKey($this->film->id)->first()?->pivot;

        $this->watched = (bool) $state?->watched;
        $this->liked = (bool) $state?->liked;
        $this->inWatchlist = $user->watchlist()->whereKey($this->film->id)->exists();
        $this->isFavorite = $user->favoriteFilms()->whereKey($this->film->id)->exists();
        $this->rating = (float) (Review::where('user_id', $user->id)
            ->where('film_id', $this->film->id)
            ->value('rating') ?? 0);
    }

    public function render()
    {
        return view('livewire.film-actions');
    }
}
