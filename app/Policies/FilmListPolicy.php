<?php

namespace App\Policies;

use App\Models\FilmList;
use App\Models\User;

class FilmListPolicy
{
    public function view(?User $user, FilmList $list): bool
    {
        return $list->visibility === 'public'
            || ($user && ($user->id === $list->user_id || $user->isAdmin()));
    }

    public function update(User $user, FilmList $list): bool
    {
        return $user->id === $list->user_id || $user->isAdmin();
    }

    public function delete(User $user, FilmList $list): bool
    {
        return $user->id === $list->user_id || $user->isAdmin();
    }
}
