<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One hint that someone is interested in a genre: they searched for it, opened a
 * film of that genre, or filtered by it. See App\Services\Recommendations.
 */
class InterestSignal extends Model
{
    public const SOURCE_SEARCH = 'search';

    public const SOURCE_VIEW = 'view';

    public const SOURCE_FILTER = 'filter';

    protected $fillable = ['user_id', 'session_key', 'genre', 'source', 'term', 'weight'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Signals belonging to a member, or to a guest's browser session.
     */
    public function scopeOwnedBy(Builder $query, ?User $user, ?string $sessionKey): Builder
    {
        return $user
            ? $query->where('user_id', $user->id)
            : $query->whereNull('user_id')->where('session_key', $sessionKey);
    }
}
