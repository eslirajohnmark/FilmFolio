<?php

namespace App\Livewire\Admin;

use App\Models\User;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * A member's online status on the admin account page, re-checked every few seconds:
 * the avatar with a green dot, or the "Online" / "Offline · 5 minutes ago" badge.
 */
class UserPresence extends Component
{
    #[Locked]
    public User $member;

    /** 'avatar' (profile picture with a dot) or 'badge' (dot and text). */
    #[Locked]
    public string $variant = 'badge';

    public function render()
    {
        return view('livewire.admin.user-presence');
    }
}
