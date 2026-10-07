<?php

namespace App\Livewire;

use Livewire\Component;

class StarRating extends Component
{
    public float $rating = 0;

    public string $model = 'rating';

    public bool $readonly = false;

    public function mount(float $rating = 0, bool $readonly = false, string $model = 'rating'): void
    {
        $this->rating = $rating;
        $this->readonly = $readonly;
        $this->model = $model;
    }

    public function setRating(float $value): void
    {
        if ($this->readonly) {
            return;
        }
        $this->rating = $value;
        $this->dispatch('rating-updated', rating: $value, model: $this->model);
    }

    public function clear(): void
    {
        if ($this->readonly) {
            return;
        }
        $this->rating = 0;
        $this->dispatch('rating-updated', rating: 0, model: $this->model);
    }

    public function render()
    {
        return view('livewire.star-rating');
    }
}
