<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * The devices a member is signed in on, read from the database session store.
 */
class BrowserSessions
{
    /**
     * @return Collection<int, object{id: string, ip: ?string, browser: string, platform: string, mobile: bool, lastActive: Carbon, isCurrent: bool}>
     */
    public function forUser(User $user, ?string $currentSessionId = null): Collection
    {
        if (! $this->isTracked()) {
            return collect();
        }

        return DB::table(config('session.table', 'sessions'))
            ->where('user_id', $user->getAuthIdentifier())
            ->orderByDesc('last_activity')
            ->get()
            ->map(function (object $session) use ($currentSessionId): object {
                $agent = $this->describeAgent((string) $session->user_agent);

                return (object) [
                    'id' => $session->id,
                    'ip' => $session->ip_address,
                    'browser' => $agent['browser'],
                    'platform' => $agent['platform'],
                    'mobile' => $agent['mobile'],
                    'lastActive' => Carbon::createFromTimestamp($session->last_activity),
                    'isCurrent' => $session->id === $currentSessionId,
                ];
            });
    }

    /**
     * Remove one session (signing that device out), or every session except $exceptSessionId.
     */
    public function destroy(User $user, ?string $sessionId = null, ?string $exceptSessionId = null): int
    {
        if (! $this->isTracked()) {
            return 0;
        }

        return DB::table(config('session.table', 'sessions'))
            ->where('user_id', $user->getAuthIdentifier())
            ->when($sessionId, fn ($query) => $query->where('id', $sessionId))
            ->when($exceptSessionId, fn ($query) => $query->where('id', '!=', $exceptSessionId))
            ->delete();
    }

    /**
     * Invalidate "remember me" cookies, so a removed device can't sign itself back in.
     */
    public function forgetRememberedDevices(User $user): void
    {
        $user->forceFill(['remember_token' => Str::random(60)])->saveQuietly();
    }

    /**
     * Sessions are only listed when they're stored in the database.
     */
    public function isTracked(): bool
    {
        return config('session.driver') === 'database';
    }

    /**
     * A readable browser and platform from a User-Agent string.
     *
     * @return array{browser: string, platform: string, mobile: bool}
     */
    public function describeAgent(string $userAgent): array
    {
        $browser = match (true) {
            str_contains($userAgent, 'Edg/') => 'Edge',
            str_contains($userAgent, 'OPR/') || str_contains($userAgent, 'Opera') => 'Opera',
            str_contains($userAgent, 'Firefox/') => 'Firefox',
            str_contains($userAgent, 'Chrome/') => 'Chrome',
            str_contains($userAgent, 'Safari/') => 'Safari',
            $userAgent === '' => 'Unknown browser',
            default => 'Browser',
        };

        $platform = match (true) {
            str_contains($userAgent, 'Windows') => 'Windows',
            str_contains($userAgent, 'iPhone') || str_contains($userAgent, 'iPad') => 'iOS',
            str_contains($userAgent, 'Android') => 'Android',
            str_contains($userAgent, 'Mac OS') => 'macOS',
            str_contains($userAgent, 'Linux') => 'Linux',
            default => 'Unknown device',
        };

        return [
            'browser' => $browser,
            'platform' => $platform,
            'mobile' => (bool) preg_match('/Mobile|Android|iPhone|iPad/', $userAgent),
        ];
    }
}
