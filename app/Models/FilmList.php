<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class FilmList extends Model
{
    /**
     * `is_official` and `is_featured` are admin-only; set them with forceFill().
     */
    protected $fillable = ['user_id', 'name', 'description', 'visibility', 'genre', 'film_count', 'cover'];

    protected $casts = [
        'is_official' => 'boolean',
        'is_featured' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
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
