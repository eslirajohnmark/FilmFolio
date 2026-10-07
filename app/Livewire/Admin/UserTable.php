<?php

namespace App\Livewire\Admin;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Searchable, filterable member list for the admin Accounts page.
 * Moderation actions live on each member's account page.
 */
class UserTable extends Component
{
    use WithPagination;

    public string $q = '';

    public string $status = 'all';

    protected $queryString = [
        'q' => ['except' => ''],
        'status' => ['except' => 'all'],
    ];

    /**
     * @var array<string, string>
     */
    public const STATUSES = [
        'all' => 'All',
        'active' => 'Active',
        'suspended' => 'Suspended',
        'banned' => 'Banned',
        'featured' => 'Featured',
        'admins' => 'Admins',
    ];

    public function updating(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $activeSuspension = fn (Builder $q) => $q->whereNotNull('suspended_at')
            ->where(fn (Builder $q) => $q->whereNull('suspended_until')->orWhere('suspended_until', '>', now()));

        $users = User::query()
            ->withCount(['reviews', 'diaryEntries'])
            ->when($this->q, fn (Builder $query) => $query->where(fn (Builder $q) => $q
                ->where('username', 'like', "%{$this->q}%")
                ->orWhere('name', 'like', "%{$this->q}%")
                ->orWhere('email', 'like', "%{$this->q}%")))
            ->when($this->status === 'active', fn (Builder $q) => $q->whereNull('banned_at')->whereNot($activeSuspension))
            ->when($this->status === 'suspended', fn (Builder $q) => $q->whereNull('banned_at')->where($activeSuspension))
            ->when($this->status === 'banned', fn (Builder $q) => $q->whereNotNull('banned_at'))
            ->when($this->status === 'featured', fn (Builder $q) => $q->where('is_featured', true))
            ->when($this->status === 'admins', fn (Builder $q) => $q->where('role', 'admin'))
            ->latest()
            ->paginate(15);

        return view('livewire.admin.user-table', [
            'users' => $users,
            'statuses' => self::STATUSES,
        ]);
    }
}
