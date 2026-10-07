<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    /**
     * Activity types that are recorded for auditing but never shown in feeds.
     *
     * @var list<string>
     */
    public const PRIVATE_TYPES = ['admin_login'];

    protected $fillable = ['user_id', 'type', 'subject_type', 'subject_id', 'meta'];

    protected $casts = ['meta' => 'array'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function subject()
    {
        return $this->morphTo();
    }

    public static function log(User $user, string $type, Model $subject, array $meta = []): self
    {
        return static::create([
            'user_id' => $user->id,
            'type' => $type,
            'subject_type' => get_class($subject),
            'subject_id' => $subject->id,
            'meta' => $meta,
        ]);
    }

    /**
     * Limit to activities that may appear in public feeds: the subject still
     * exists, the type is not audit-only, and lists are public.
     */
    public function scopeVisibleInFeeds(Builder $query): Builder
    {
        return $query
            ->whereNotIn('type', self::PRIVATE_TYPES)
            ->whereHasMorph('subject', '*', function (Builder $subject, string $type) {
                if ($type === FilmList::class) {
                    $subject->where('visibility', 'public');
                }
            });
    }
}
