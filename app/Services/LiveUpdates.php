<?php

namespace App\Services;

use App\Models\Film;
use App\Models\FilmList;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Lets open pages pick up changes without a reload. A "content version" number changes whenever
 * films, lists or featured members change; every open page checks it every few seconds and, when
 * it has moved on, quietly swaps in the page's fresh content (see partials/scripts).
 */
class LiveUpdates
{
    public const CACHE_KEY = 'live:version';

    /**
     * Header the browser sends when it fetches a page only to refresh it in place.
     */
    public const REFRESH_HEADER = 'X-Live-Refresh';

    /**
     * Member fields admins change from the accounts page that show on the site.
     */
    public const WATCHED_USER_FIELDS = ['is_featured', 'suspended_at', 'suspended_until', 'banned_at'];

    public function version(): int
    {
        return (int) Cache::get(self::CACHE_KEY, 0);
    }

    public function changed(): void
    {
        Cache::forever(self::CACHE_KEY, (int) floor(microtime(true) * 1000));
    }

    /**
     * Whether this request only refreshes a page that is already open, so it shouldn't
     * count as the member visiting or searching again.
     */
    public static function isRefresh(?Request $request = null): bool
    {
        return ($request ?? request())->hasHeader(self::REFRESH_HEADER);
    }

    /**
     * Bump the version when anything the site shows from films, lists or featured members changes.
     */
    public function watchModels(): void
    {
        foreach ([Film::class, FilmList::class] as $model) {
            $model::saved(fn () => $this->changed());
            $model::deleted(fn () => $this->changed());
        }

        User::saved(function (User $user): void {
            if ($user->wasChanged(self::WATCHED_USER_FIELDS)) {
                $this->changed();
            }
        });
        User::deleted(fn () => $this->changed());
    }
}
