<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\DiaryEntry;
use App\Models\Film;
use App\Models\Review;
use App\Services\Recommendations;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(Recommendations $recommendations): View
    {
        $user = auth()->user();

        if (! $user) {
            return view('home', [
                'user' => null,
                'justReviewed' => $this->justReviewed(),
                'suggestions' => $recommendations->suggestionsFor(limit: 5),
            ]);
        }

        $followingIds = $user->following()->pluck('users.id');

        return view('home', [
            'user' => $user,
            'suggestions' => $recommendations->suggestionsFor(limit: 5),
            'friendsWatching' => DiaryEntry::with(['film', 'user'])
                ->whereIn('user_id', $followingIds)
                ->latest('watched_on')
                ->latest('id')
                ->take(5)
                ->get(),
            'popularFilms' => Film::popularThisWeek()->take(5)->get(),
            'feed' => Activity::with(['user', 'subject'])
                ->visibleInFeeds()
                ->whereIn('user_id', $followingIds->push($user->id))
                ->latest()
                ->take(8)
                ->get(),
        ]);
    }

    /**
     * The five films reviewed most recently, falling back to the newest films.
     *
     * @return Collection<int, Film>
     */
    protected function justReviewed(): Collection
    {
        $films = Review::with('film')
            ->latest()
            ->take(40)
            ->get()
            ->pluck('film')
            ->unique('id')
            ->take(5);

        if ($films->count() < 5) {
            $films = $films->concat(
                Film::whereNotIn('id', $films->pluck('id'))->latest()->take(5 - $films->count())->get()
            );
        }

        return $films->values();
    }
}
