<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * Moderation and curation fields are deliberately not fillable; set them with forceFill().
     */
    protected $fillable = [
        'name', 'email', 'password',
        'username', 'bio', 'avatar',
    ];

    protected $hidden = ['password', 'remember_token'];

    /**
     * Matches the column default, so new members get activity emails before they're reloaded.
     */
    protected $attributes = ['email_notifications' => true];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'suspended_at' => 'datetime',
        'suspended_until' => 'datetime',
        'banned_at' => 'datetime',
        'is_featured' => 'boolean',
        'email_notifications' => 'boolean',
        'password' => 'hashed',
    ];

    protected static function booted(): void
    {
        static::deleted(function (User $user) {
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }
        });
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function filmLists()
    {
        return $this->hasMany(FilmList::class);
    }

    public function diaryEntries()
    {
        return $this->hasMany(DiaryEntry::class);
    }

    public function activities()
    {
        return $this->hasMany(Activity::class);
    }

    public function watchlist()
    {
        return $this->belongsToMany(Film::class, 'watchlists');
    }

    /**
     * Every film this member has a watched/liked state for.
     */
    public function films()
    {
        return $this->belongsToMany(Film::class)->withPivot(['watched', 'liked'])->withTimestamps();
    }

    public function watchedFilms()
    {
        return $this->films()->wherePivot('watched', true);
    }

    public function likedFilms()
    {
        return $this->films()->wherePivot('liked', true);
    }

    public function favoriteFilms()
    {
        return $this->belongsToMany(Film::class, 'favorite_films')
            ->withPivot('position')
            ->orderBy('favorite_films.position');
    }

    /**
     * Mark a film as seen. Like Letterboxd, watching a film takes it off the watchlist.
     */
    public function markWatched(Film $film): void
    {
        $this->films()->syncWithoutDetaching([$film->id => ['watched' => true]]);
        $this->watchlist()->detach($film->id);
    }

    public function following()
    {
        return $this->belongsToMany(User::class, 'follows', 'follower_id', 'following_id')
            ->withTimestamps();
    }

    public function followers()
    {
        return $this->belongsToMany(User::class, 'follows', 'following_id', 'follower_id')
            ->withTimestamps();
    }

    /**
     * Suspended right now: a suspension exists and has not expired
     * (no end date means until an admin lifts it).
     */
    public function isSuspended(): bool
    {
        return $this->suspended_at !== null
            && ($this->suspended_until === null || $this->suspended_until->isFuture());
    }

    public function isBanned(): bool
    {
        return $this->banned_at !== null;
    }

    /**
     * One of: banned, suspended, active. (Administrators live in the `admins` table.)
     */
    public function accountStatus(): string
    {
        return match (true) {
            $this->isBanned() => 'banned',
            $this->isSuspended() => 'suspended',
            default => 'active',
        };
    }

    /**
     * Why this member may not sign in, or null if they can.
     */
    public function accessBlockedMessage(): ?string
    {
        if ($this->isBanned()) {
            return 'This account has been banned.';
        }

        if ($this->isSuspended()) {
            return $this->suspended_until
                ? 'This account is suspended until '.$this->suspended_until->format('M j, Y g:i A').'.'
                : 'This account has been suspended.';
        }

        return null;
    }

    public function listLikes()
    {
        return $this->belongsToMany(FilmList::class, 'list_likes')->withTimestamps();
    }

    public function isFollowing(User $other): bool
    {
        return $this->following()->where('following_id', $other->id)->exists();
    }

    /**
     * Uploaded avatar, or the bundled default (served locally so it works offline).
     */
    public function avatarUrl(): string
    {
        return $this->avatar
            ? asset('storage/'.$this->avatar)
            : asset('images/default-avatar.svg');
    }
}
