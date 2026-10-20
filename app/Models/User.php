<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
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
        'last_seen_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * A member counts as online when they loaded a page, or an open page checked in, this recently.
     * Open pages check in every few seconds (see partials/scripts), so two minutes allows for
     * browsers slowing timers in background tabs.
     */
    public const ONLINE_WITHIN_SECONDS = 120;

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

    /**
     * Rate a film from 0.5 to 5 stars in half-star steps. Rating a film marks it watched.
     * Returns false, changing nothing, when the rating is out of range or not a half step.
     */
    public function rateFilm(Film $film, float $rating): bool
    {
        if ($rating < 0.5 || $rating > 5 || fmod($rating * 2, 1.0) !== 0.0) {
            return false;
        }

        $review = Review::updateOrCreate(
            ['user_id' => $this->id, 'film_id' => $film->id],
            ['rating' => $rating],
        );
        $this->markWatched($film);

        if ($review->wasRecentlyCreated || $review->wasChanged('rating')) {
            Activity::log($this, 'rated', $film, ['rating' => $rating]);
        }

        return true;
    }

    /**
     * This member's rating of every film they have rated, keyed by film id. Loaded once per
     * request, so a page of poster cards can show the member's own stars without a query each.
     *
     * @return Collection<int, float>
     */
    public function filmRatings(): Collection
    {
        return once(fn () => $this->reviews()->pluck('rating', 'film_id')->map(fn ($rating): float => (float) $rating));
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

    public function isOnline(): bool
    {
        return $this->last_seen_at !== null
            && $this->last_seen_at->gte(now()->subSeconds(self::ONLINE_WITHIN_SECONDS));
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
