<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Review extends Model
{
    use HasFactory;

    public const AGREE = 1;

    public const DISAGREE = -1;

    protected $fillable = ['user_id', 'film_id', 'rating', 'body', 'agrees', 'disagrees'];

    protected $casts = [
        'rating' => 'float',
        'agrees' => 'integer',
        'disagrees' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function film()
    {
        return $this->belongsTo(Film::class);
    }

    /**
     * Members who agreed (vote = 1) or disagreed (vote = -1) with this review.
     */
    public function voters()
    {
        return $this->belongsToMany(User::class, 'review_votes')->withPivot('vote')->withTimestamps();
    }

    public function comments()
    {
        return $this->hasMany(ReviewComment::class)->oldest();
    }

    /**
     * Agree or disagree with this review. Casting the same vote again removes it.
     * Returns the member's vote afterwards (1, -1 or 0).
     */
    public function castVote(User $user, int $vote): int
    {
        return DB::transaction(function () use ($user, $vote) {
            $current = (int) $this->voters()->whereKey($user->id)->value('vote');

            if ($current === $vote) {
                $this->voters()->detach($user->id);
                $result = 0;
            } else {
                $this->voters()->syncWithoutDetaching([$user->id => ['vote' => $vote]]);
                $result = $vote;
            }

            static::withoutTimestamps(fn () => $this->forceFill([
                'agrees' => $this->voters()->wherePivot('vote', self::AGREE)->count(),
                'disagrees' => $this->voters()->wherePivot('vote', self::DISAGREE)->count(),
            ])->saveQuietly());

            return $result;
        });
    }

    public function voteBy(?User $user): int
    {
        return $user ? (int) $this->voters()->whereKey($user->id)->value('vote') : 0;
    }

    public function starsDisplay(): string
    {
        $full = floor($this->rating);
        $half = ($this->rating - $full) >= 0.5;
        $empty = 5 - $full - ($half ? 1 : 0);

        return str_repeat('★', (int) $full)
             .($half ? '½' : '')
             .str_repeat('☆', max(0, $empty));
    }
}
