<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\OfficialListRequest;
use App\Models\Film;
use App\Models\FilmList;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ListController extends Controller
{
    public function index(Request $request): View
    {
        $filter = $request->query('filter', 'all');
        $q = trim((string) $request->query('q', ''));

        $lists = FilmList::with('user')
            ->withCount(['films', 'likes'])
            ->when($q !== '', fn ($query) => $query->where('name', 'like', "%{$q}%"))
            ->when($filter === 'official', fn ($query) => $query->official())
            ->when($filter === 'featured', fn ($query) => $query->featured())
            ->when($filter === 'private', fn ($query) => $query->where('visibility', 'private'))
            ->latest('updated_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.lists.index', compact('lists', 'filter', 'q'));
    }

    public function create(): View
    {
        return view('admin.lists.create', [
            'genres' => Film::whereNotNull('genre')->distinct()->orderBy('genre')->pluck('genre'),
        ]);
    }

    /**
     * Create an official "Crew Pick" list, optionally auto-filled with the
     * highest-rated films of a genre.
     */
    public function store(OfficialListRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $list = new FilmList([
            'user_id' => $request->user()->id,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'genre' => $data['genre'] ?? null,
            'visibility' => 'public',
        ]);
        $list->forceFill(['is_official' => true, 'is_featured' => $request->boolean('is_featured')])->save();

        $autoFill = (int) ($data['auto_fill'] ?? 0);
        if ($autoFill > 0 && $list->genre) {
            $films = Film::where('genre', $list->genre)
                ->withAvg('reviews', 'rating')
                ->withCount('reviews')
                ->orderByDesc('reviews_avg_rating')
                ->orderByDesc('reviews_count')
                ->take($autoFill)
                ->pluck('id');

            $list->films()->attach($films->values()->mapWithKeys(fn ($id, $i) => [$id => ['position' => $i + 1]]));
        }

        return redirect()->route('lists.show', $list)
            ->with('status', "Crew Pick \"{$list->name}\" created with {$list->films()->count()} films.");
    }

    public function toggleFeatured(FilmList $list): RedirectResponse
    {
        if ($list->visibility !== 'public' && ! $list->is_featured) {
            return back()->with('status', 'Only public lists can be featured.');
        }

        $list->forceFill(['is_featured' => ! $list->is_featured])->save();

        return back()->with('status', $list->is_featured ? "\"{$list->name}\" is now featured." : "\"{$list->name}\" is no longer featured.");
    }

    public function destroy(FilmList $list): RedirectResponse
    {
        $list->delete();

        return back()->with('status', "List \"{$list->name}\" deleted.");
    }
}
