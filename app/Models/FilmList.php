<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class FilmList extends Model
{
    /**
     * `is_official`, `is_featured` and `admin_id` are admin-only; set them with forceFill().
     */
    protected $fillable = ['user_id', 'name', 'description', 'visibility', 'genre', 'film_count', 'cover'];

    protected $casts = [
        'is_official' => 'boolean',
        'is_featured' => 'boolean',
    ];

    /**
     * The member who made the list (null for official lists made by an admin).
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The admin who made an official "Crew Pick" list.
     */
    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }

    /**
     * Name shown as the list's author: the member's username, or "FilmFolio Crew" for admin lists.
     */
    public function ownerName(): string
    {
        return $this->user?->username ?? $this->user?->name ?? 'FilmFolio Crew';
    }

    public function films()
    {
        return $this->belongsToMany(Film::class, 'list_items')->withPivot('position');
    }

    public function likes()
    {
        return $this->belongsToMany(User::class, 'list_likes')->withTimestamps();
    }

    public function scopePublic(Builder $query): Builder
    {
        return $query->where('visibility', 'public');
    }

    /**
     * Admin-made "Crew Picks".
     */
    public function scopeOfficial(Builder $query): Builder
    {
        return $query->where('is_official', true);
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    /**
     * Public lists ordered by likes received in the last 30 days, then all-time likes.
     */
    public function scopeRecentlyPopular(Builder $query): Builder
    {
        return $query->public()
            ->withCount([
                'likes as recent_likes_count' => fn ($q) => $q->where('list_likes.created_at', '>=', now()->subDays(30)),
                'likes',
            ])
            ->orderByDesc('recent_likes_count')
            ->orderByDesc('likes_count')
            ->latest('updated_at');
    }
}
