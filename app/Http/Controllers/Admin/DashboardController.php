<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DiaryEntry;
use App\Models\Film;
use App\Models\FilmList;
use App\Models\Review;
use App\Models\ReviewComment;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        return view('admin.dashboard', [
            'members' => [
                'total' => User::count(),
                'newThisWeek' => User::where('created_at', '>=', now()->subWeek())->count(),
                'suspended' => User::whereNotNull('suspended_at')
                    ->where(fn (Builder $q) => $q->whereNull('suspended_until')->orWhere('suspended_until', '>', now()))
                    ->count(),
                'banned' => User::whereNotNull('banned_at')->count(),
            ],
            'catalogue' => [
                'films' => Film::count(),
                'teasers' => Film::where('is_teaser', true)->count(),
                'lists' => FilmList::count(),
                'officialLists' => FilmList::official()->count(),
            ],
            'periods' => $this->periodStats(),
            'genres' => $this->popularBy('films.genre'),
            'decades' => $this->popularBy('films.year - (films.year % 10)'),
            'weekly' => $this->weeklyActivity(12),
        ]);
    }

    /**
     * New members, reviews, logs, lists and comments for the last 7 / 30 / 365 days.
     *
     * @return array<string, array<string, int>>
     */
    protected function periodStats(): array
    {
        $periods = ['This week' => now()->subWeek(), 'This month' => now()->subMonth(), 'This year' => now()->subYear()];
        $metrics = [
            'New members' => User::query(),
            'Reviews & ratings' => Review::query(),
            'Diary logs' => DiaryEntry::query(),
            'Lists created' => FilmList::query(),
            'Comments' => ReviewComment::query(),
        ];

        $stats = [];
        foreach ($metrics as $metric => $query) {
            foreach ($periods as $period => $since) {
                $stats[$metric][$period] = (clone $query)->where('created_at', '>=', $since)->count();
            }
        }

        return $stats;
    }

    /**
     * Engagement (diary logs + reviews) grouped by a film column expression, most popular first.
     *
     * @return Collection<string, int>
     */
    protected function popularBy(string $expression): Collection
    {
        $count = fn (string $table) => DB::table($table)
            ->join('films', 'films.id', '=', "{$table}.film_id")
            ->when(
                $expression === 'films.genre',
                fn ($q) => $q->whereNotNull('films.genre'),
                fn ($q) => $q->where('films.year', '>', 0),
            )
            ->selectRaw("{$expression} AS bucket, COUNT(*) AS total")
            ->groupBy(DB::raw($expression))
            ->pluck('total', 'bucket');

        $totals = collect();
        foreach ([$count('diary_entries'), $count('reviews')] as $counts) {
            foreach ($counts as $bucket => $total) {
                $totals[(string) $bucket] = ($totals[(string) $bucket] ?? 0) + (int) $total;
            }
        }

        return $totals->sortDesc()->take(8);
    }

    /**
     * Reviews + diary logs per week for the last N weeks (oldest first).
     *
     * @return Collection<int, array{start: string, label: string, total: int}>
     */
    protected function weeklyActivity(int $weeks): Collection
    {
        $start = now()->startOfWeek()->subWeeks($weeks - 1);

        $dates = Review::where('created_at', '>=', $start)->pluck('created_at')
            ->concat(DiaryEntry::where('created_at', '>=', $start)->pluck('created_at'));

        return collect(range(0, $weeks - 1))->map(function (int $i) use ($start, $dates) {
            $from = $start->copy()->addWeeks($i);
            $to = $from->copy()->addWeek();

            return [
                'start' => $from->toDateString(),
                'label' => $from->format('M j'),
                'total' => $dates->filter(fn (Carbon $date) => $date->gte($from) && $date->lt($to))->count(),
            ];
        });
    }
}
