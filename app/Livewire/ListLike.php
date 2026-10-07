<?php

namespace App\Livewire;

use App\Models\FilmList;
use Livewire\Component;

/**
 * Heart button on a list. Likes rank the "Recently Popular" lists.
 */
class ListLike extends Component
{
    public FilmList $list;

    public bool $liked = false;

    public int $count = 0;

    public function mount(FilmList $list): void
    {
        $this->list = $list;
        $this->refreshState();
    }

    public function toggle(): void
    {
        if (! auth()->check()) {
            $this->redirect(route('login'));

            return;
        }

        $this->authorize('view', $this->list);

        if ($this->liked) {
            $this->list->likes()->detach(auth()->id());
        } else {
            $this->list->likes()->syncWithoutDetaching([auth()->id()]);
        }

        $this->refreshState();
    }

    protected function refreshState(): void
    {
        $this->count = $this->list->likes()->count();
        $this->liked = auth()->check() && $this->list->likes()->whereKey(auth()->id())->exists();
    }

    public function render()
    {
        return view('livewire.list-like');
    }
}
