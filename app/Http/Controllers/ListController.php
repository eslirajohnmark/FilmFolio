<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Film;
use App\Models\FilmList;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ListController extends Controller
{
    public function index(): View
    {
        $cards = fn ($query) => $query
            ->with(['user', 'films' => fn ($films) => $films->orderBy('list_items.position')->limit(5)])
            ->withCount('films');

        return view('lists.index', [
            'featured' => $cards(FilmList::public()->featured())->latest('updated_at')->take(6)->get(),
            'popular' => $cards(FilmList::recentlyPopular())->take(6)->get(),
            'crewPicks' => $cards(FilmList::public()->official())->latest('updated_at')->take(6)->get(),
            'mine' => auth()->check()
                ? $cards(auth()->user()->filmLists())->latest('updated_at')->get()
                : collect(),
            'recent' => $cards(FilmList::public())->latest('updated_at')->paginate(12),
        ]);
    }

    public function show(FilmList $list): View
    {
        Gate::authorize('view', $list);

        $films = $list->films()->orderBy('list_items.position')->withCardStats()->get();

        return view('lists.show', [
            'list' => $list->load('user')->loadCount('likes'),
            'films' => $films,
            'reviews' => Review::with(['user', 'film'])
                ->withCount('comments')
                ->whereIn('film_id', $films->pluck('id'))
                ->whereNotNull('body')
                ->orderByDesc('agrees')
                ->latest()
                ->take(6)
                ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|min:3|max:120',
            'visibility' => 'required|in:public,private',
        ]);

        $list = FilmList::create([
            'user_id' => auth()->id(),
            'name' => $data['name'],
            'visibility' => $data['visibility'],
        ]);

        Activity::log(auth()->user(), 'listed', $list);

        return back()->with('status', "\"{$list->name}\" created!");
    }

    public function update(Request $request, FilmList $list): RedirectResponse
    {
        Gate::authorize('update', $list);

        $list->update($request->validate([
            'name' => 'required|string|min:3|max:120',
            'description' => 'nullable|string|max:1000',
            'visibility' => 'required|in:public,private',
        ]));

        return redirect()->route('lists.show', $list)->with('status', 'List updated.');
    }

    public function destroy(FilmList $list): RedirectResponse
    {
        Gate::authorize('delete', $list);

        $list->delete();

        return redirect()->route('lists.index')->with('status', "\"{$list->name}\" deleted.");
    }

    public function removeFilm(FilmList $list, Film $film): RedirectResponse
    {
        Gate::authorize('update', $list);

        $list->films()->detach($film);

        return back()->with('status', "Removed \"{$film->title}\" from the list.");
    }
}
