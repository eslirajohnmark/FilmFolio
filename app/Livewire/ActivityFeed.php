<?php

namespace App\Livewire;

use App\Models\Activity;
use Livewire\Component;
use Livewire\WithPagination;

class ActivityFeed extends Component
{
    use WithPagination;

    public string $scope = 'following';

    public function updatingScope(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $query = Activity::with(['user', 'subject'])
            ->visibleInFeeds()
            ->orderByDesc('created_at');

        if ($this->scope === 'following' && auth()->check()) {
            $ids = auth()->user()->following()
                ->pluck('users.id')
                ->push(auth()->id());
            $query->whereIn('user_id', $ids);
        } elseif ($this->scope === 'following') {
            $query->whereRaw('1 = 0');
        }

        return view('livewire.activity-feed', [
            'activities' => $query->paginate(20),
        ]);
    }
}
