<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class TrackLastSeen
{
    /**
     * Seconds between writes: a member clicking around writes their "last seen" at most once a minute.
     */
    public const WRITE_EVERY_SECONDS = 60;

    /**
     * Record when the signed-in member was last active, for the admin's online indicator.
     * Written with a plain query so it never changes the member's updated_at or fires model events.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof User && ($user->last_seen_at === null || $user->last_seen_at->lt(now()->subSeconds(self::WRITE_EVERY_SECONDS)))) {
            $now = now();
            DB::table('users')->where('id', $user->id)->update(['last_seen_at' => $now]);
            $user->setAttribute('last_seen_at', $now)->syncOriginalAttribute('last_seen_at');
        }

        return $next($request);
    }
}
