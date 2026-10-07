<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Film;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FilmController extends Controller
{
    public function index(): View
    {
        return view('films.index', [
            'popular' => Film::popularThisWeek()->take(5)->get(),
        ]);
    }

    public function show(Film $film): View
    {
        $film->load('tags')
            ->loadCount(['watchers', 'likers', 'lists', 'reviews'])
            ->loadAvg('reviews', 'rating');

        $histogram = Review::where('film_id', $film->id)
            ->selectRaw('rating, COUNT(*) AS total')
            ->groupBy('rating')
            ->pluck('total', 'rating')
            ->mapWithKeys(fn ($total, $rating) => [number_format((float) $rating, 1) => (int) $total]);

        return view('films.show', [
            'film' => $film,
            'reviews' => $film->reviews()
                ->with('user')
                ->withCount('comments')
                ->whereNotNull('body')
                ->orderByDesc('agrees')
                ->latest()
                ->take(12)
                ->get(),
            'histogram' => collect(range(1, 10))
                ->mapWithKeys(fn ($half) => [number_format($half / 2, 1) => $histogram[number_format($half / 2, 1)] ?? 0]),
            'myReview' => auth()->check()
                ? Review::where('film_id', $film->id)->where('user_id', auth()->id())->first()
                : null,
        ]);
    }

    public function storeReview(Request $request, Film $film): RedirectResponse
    {
        $data = $request->validate([
            'rating' => 'required|numeric|min:0.5|max:5',
            'body' => 'nullable|string|max:5000',
        ]);

        $review = Review::updateOrCreate(
            ['user_id' => auth()->id(), 'film_id' => $film->id],
            $data
        );
        $request->user()->markWatched($film);

        if ($review->wasRecentlyCreated || $review->wasChanged()) {
            Activity::log(auth()->user(), 'reviewed', $film, [
                'rating' => $data['rating'],
                'body' => $data['body'] ?? '',
            ]);
        }

        return back()->with('status', 'Review saved!');
    }
}
