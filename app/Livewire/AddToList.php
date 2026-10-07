<?php

namespace App\Livewire;

use App\Models\Film;
use App\Models\FilmList;
use Livewire\Component;

class AddToList extends Component
{
    public Film $film;

    public bool $open = false;

    public function toggle(int $listId): void
    {
        $list = FilmList::findOrFail($listId);
        $this->authorize('update', $list);

        if ($list->films()->whereKey($this->film->id)->exists()) {
            $list->films()->detach($this->film);
            $this->dispatch('notify', message: "Removed from \"{$list->name}\".");

            return;
        }

        $list->films()->attach($this->film, [
            'position' => (int) $list->films()->max('list_items.position') + 1,
        ]);
        $this->dispatch('notify', message: "Added to \"{$list->name}\".");
    }

    public function render()
    {
        return view('livewire.add-to-list', [
            'lists' => auth()->user()
                ->filmLists()
                ->withExists(['films as has_film' => fn ($query) => $query->whereKey($this->film->id)])
                ->orderBy('name')
                ->get(),
        ]);
    }
}
