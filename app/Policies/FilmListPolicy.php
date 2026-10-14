<?php

namespace App\Policies;

use App\Models\FilmList;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class FilmListPolicy
{
    /**
     * Public lists are open to everyone; private ones to their owner and signed-in admins.
     */
    public function view(?User $user, FilmList $list): bool
    {
        return $list->visibility === 'public'
            || ($user && $user->id === $list->user_id)
            || Auth::guard('admin')->check();
    }

    public function update(User $user, FilmList $list): bool
    {
        return $user->id === $list->user_id;
    }

    public function delete(User $user, FilmList $list): bool
    {
        return $user->id === $list->user_id;
    }
}
