<?php

namespace App\Livewire;

use App\Models\Review;
use Livewire\Component;

class ReviewFeed extends Component
{
    public int $limit = 6;

    public function render()
    {
        $popular = fn () => Review::with(['user', 'film'])
            ->withCount('comments')
            ->whereNotNull('body')
            ->orderByDesc('agrees')
            ->latest()
            ->take($this->limit);

        $reviews = $popular()->where('created_at', '>=', now()->subWeek())->get();
        $isThisWeek = $reviews->isNotEmpty();

        return view('livewire.review-feed', [
            'reviews' => $isThisWeek ? $reviews : $popular()->get(),
            'heading' => $isThisWeek ? 'Popular Reviews This Week' : 'Popular Reviews',
        ]);
    }
}
