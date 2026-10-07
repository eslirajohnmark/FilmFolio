<?php

namespace App\Livewire;

use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;

class MemberSearch extends Component
{
    use WithPagination;

    public string $q = '';

    public function updating(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $users = User::query()
            ->when(trim($this->q) !== '', fn ($query) => $query->where(fn ($match) => $match
                ->where('username', 'like', '%'.trim($this->q).'%')
                ->orWhere('name', 'like', '%'.trim($this->q).'%')))
            ->withCount(['reviews', 'filmLists', 'followers'])
            ->orderByDesc('reviews_count')
            ->paginate(8);

        return view('livewire.member-search', [
            'users' => $users,
            'followingIds' => auth()->check()
                ? auth()->user()->following()->whereIn('users.id', $users->pluck('id'))->pluck('users.id')->all()
                : [],
        ]);
    }
}
