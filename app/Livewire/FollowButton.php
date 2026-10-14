<?php

namespace App\Livewire;

use App\Models\Activity;
use App\Models\Follow;
use App\Models\User;
use App\Notifications\NewFollower;
use Livewire\Component;

class FollowButton extends Component
{
    public User $user;

    public bool $following = false;

    public int $followersCount = 0;

    /**
     * Callers rendering many buttons can pass precomputed values to avoid per-button queries.
     */
    public function mount(User $user, ?int $followersCount = null, ?bool $following = null): void
    {
        $this->user = $user;
        $this->followersCount = $followersCount ?? $user->followers_count ?? $user->followers()->count();
        $this->following = $following ?? (auth()->check() && auth()->user()->isFollowing($user));
    }

    public function toggle(): void
    {
        if (! auth()->check()) {
            $this->redirect(route('login'));

            return;
        }

        if (auth()->id() === $this->user->id) {
            return;
        }

        if ($this->following) {
            Follow::where('follower_id', auth()->id())
                ->where('following_id', $this->user->id)
                ->delete();
            $this->followersCount = max(0, $this->followersCount - 1);
        } else {
            $follow = Follow::firstOrCreate([
                'follower_id' => auth()->id(),
                'following_id' => $this->user->id,
            ]);

            if ($follow->wasRecentlyCreated) {
                $this->followersCount++;
                Activity::log(auth()->user(), 'followed', $this->user);
                $this->user->notify(new NewFollower(auth()->user()));
            }
        }

        $this->following = ! $this->following;
        $this->dispatch('followers-changed');
    }

    public function render()
    {
        return view('livewire.follow-button');
    }
}
