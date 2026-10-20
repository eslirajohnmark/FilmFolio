<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DiaryEntry;
use App\Models\FilmList;
use App\Models\Review;
use App\Models\ReviewComment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/**
 * What members have been doing: reviews and ratings, diary logs, comments and lists, for a
 * period (the dashboard's "this week / month / year") or a single week from its activity chart.
 */
class ActivityController extends Controller
{
    /**
     * @var array<string, string>
     */
    public const TYPES = [
        'reviews' => 'Reviews & ratings',
        'logs' => 'Diary logs',
        'comments' => 'Comments',
        'lists' => 'Lists',
    ];

    /**
     * @var array<string, string>
     */
    public const PERIODS = [
        'week' => 'This week',
        'month' => 'This month',
        'year' => 'This year',
        'all' => 'All time',
    ];

    public function index(Request $request): View
    {
        $type = array_key_exists($request->query('type'), self::TYPES) ? $request->query('type') : 'reviews';
        $period = array_key_exists($request->query('period'), self::PERIODS) ? $request->query('period') : 'week';
        $week = $this->weekStarting($request->query('week'));

        $inRange = function (Builder $query) use ($period, $week): Builder {
            if ($week) {
                return $query->where('created_at', '>=', $week)->where('created_at', '<', $week->copy()->addWeek());
            }

            return $query->when(self::since($period), fn (Builder $q, Carbon $since) => $q->where('created_at', '>=', $since));
        };

        $queries = [
            'reviews' => fn () => Review::query()->with(['user', 'film']),
            'logs' => fn () => DiaryEntry::query()->with(['user', 'film']),
            'comments' => fn () => ReviewComment::query()->with(['user', 'review.film']),
            'lists' => fn () => FilmList::query()->with('user')->withCount('films'),
        ];

        return view('admin.activity.index', [
            'type' => $type,
            'period' => $period,
            'week' => $week,
            'counts' => collect($queries)->map(fn ($query) => $inRange($query())->count()),
            'items' => $inRange($queries[$type]())->latest()->paginate(20)->withQueryString(),
        ]);
    }

    /**
     * Start of each period, matching the dashboard's weekly / monthly / yearly statistics.
     */
    public static function since(string $period): ?Carbon
    {
        return match ($period) {
            'week' => now()->subWeek(),
            'month' => now()->subMonth(),
            'year' => now()->subYear(),
            default => null,
        };
    }

    /**
     * A week from the dashboard chart, given as its first day (e.g. 2026-10-12); null when absent or invalid.
     */
    protected function weekStarting(mixed $date): ?Carbon
    {
        if (! is_string($date) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return null;
        }

        try {
            return Carbon::createFromFormat('!Y-m-d', $date);
        } catch (\Throwable) {
            return null;
        }
    }
}
