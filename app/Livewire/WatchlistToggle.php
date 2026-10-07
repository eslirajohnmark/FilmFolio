<?php

namespace App\Livewire;

use App\Models\Activity;
use App\Models\Film;
use App\Models\Watchlist;
use Livewire\Component;

class WatchlistToggle extends Component
{
    public Film $film;

    public bool $inList = false;

    public function mount(Film $film): void
    {
        $this->film = $film;
        $this->inList = auth()->check()
            && Watchlist::where('user_id', auth()->id())
                ->where('film_id', $film->id)
                ->exists();
    }

    public function toggle(): void
    {
        if (! auth()->check()) {
            $this->redirect(route('login'));

            return;
        }

        if ($this->inList) {
            Watchlist::where('user_id', auth()->id())
                ->where('film_id', $this->film->id)
                ->delete();
        } else {
            Watchlist::firstOrCreate([
                'user_id' => auth()->id(),
                'film_id' => $this->film->id,
            ]);
            Activity::log(auth()->user(), 'watchlist_added', $this->film);
        }

        $this->inList = ! $this->inList;
        $this->dispatch('watchlist-updated');
    }

    public function render()
    {
        return view('livewire.watchlist-toggle');
    }
}
