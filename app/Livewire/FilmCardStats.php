<?php

namespace App\Livewire;

use App\Models\Film;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * The bottom row of a poster card: half-star rating the member can tap, and how many
 * members watched and liked the film. The card passes in the numbers it already loaded
 * (see Film::scopeWithCardStats), so rendering a grid of cards adds no queries.
 */
class FilmCardStats extends Component
{
    #[Locked]
    public Film $film;

    /** The signed-in member's own rating; 0 when they haven't rated the film. */
    public float $rating = 0;

    #[Locked]
    public float $average = 0;

    #[Locked]
    public int $views = 0;

    #[Locked]
    public int $hearts = 0;

    public function mount(Film $film, float $rating = 0, float $average = 0, int $views = 0, int $hearts = 0): void
    {
        $this->film = $film;
        $this->rating = $rating;
        $this->average = $average;
        $this->views = $views;
        $this->hearts = $hearts;
    }

    public function rate(float $value): void
    {
        $user = auth()->user();

        if (! $user) {
            $this->redirect(route('login'));

            return;
        }

        if (! $user->rateFilm($this->film, $value)) {
            return;
        }

        $this->rating = $value;

        $this->film->loadCount(['watchers', 'likers'])->loadAvg('reviews', 'rating');
        $this->average = (float) $this->film->reviews_avg_rating;
        $this->views = (int) $this->film->watchers_count;
        $this->hearts = (int) $this->film->likers_count;

        $this->dispatch('notify', message: "Rated {$this->film->title} {$value}★");
    }

    public function render()
    {
        return view('livewire.film-card-stats');
    }
}
