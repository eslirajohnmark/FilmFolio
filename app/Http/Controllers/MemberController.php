<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MemberController extends Controller
{
    /**
     * @var list<string>
     */
    public const PROFILE_TABS = ['profile', 'activity', 'films', 'diary', 'reviews', 'ratings', 'watchlist', 'lists', 'likes'];

    public function index(Request $request): View
    {
        $cards = fn () => User::query()->withCount(['reviews', 'filmLists', 'followers']);

        $featured = $cards()->where('is_featured', true)->latest('updated_at')->take(6)->get();
        $popular = $cards()->orderByDesc('followers_count')->orderByDesc('reviews_count')->take(6)->get();

        return view('members.index', [
            'featured' => $featured,
            'popular' => $popular,
            'followingIds' => $request->user()
                ? $request->user()->following()
                    ->whereIn('users.id', $featured->pluck('id')->merge($popular->pluck('id')))
                    ->pluck('users.id')
                    ->all()
                : [],
        ]);
    }

    public function show(Request $request, User $user): View
    {
        $tab = in_array($request->query('tab'), self::PROFILE_TABS, true) ? $request->query('tab') : 'profile';
        $isOwner = $request->user()?->is($user) || auth('admin')->check();

        $user->loadCount([
            'watchedFilms',
            'filmLists',
            'followers',
            'following',
            'diaryEntries as this_year_count' => fn ($query) => $query->whereYear('watched_on', now()->year),
        ]);

        $data = ['user' => $user, 'tab' => $tab, 'isOwner' => $isOwner];

        if ($tab === 'profile') {
            return view('profile.show', $data + [
                'favorites' => $user->favoriteFilms()->withCardStats()->get(),
                'recentDiary' => $user->diaryEntries()->with(['film' => fn ($query) => $query->withCardStats()])
                    ->latest('watched_on')->latest('id')->take(5)->get(),
                'recentReviews' => $user->reviews()->with(['film', 'user'])->withCount('comments')
                    ->whereNotNull('body')->latest()->take(4)->get(),
            ]);
        }

        $items = match ($tab) {
            'activity' => $user->activities()->with(['user', 'subject'])->visibleInFeeds()->latest()->paginate(20),
            'ratings' => $user->reviews()->with(['film' => fn ($query) => $query->withCardStats()])->latest('updated_at')->paginate(30),
            'films' => $user->watchedFilms()->withCardStats()->orderByPivot('updated_at', 'desc')->paginate(30),
            'reviews' => $user->reviews()->with(['film', 'user'])->withCount('comments')->latest()->paginate(12),
            'watchlist' => $user->watchlist()->orderByDesc('watchlists.id')->withCardStats()->paginate(30),
            'likes' => $user->likedFilms()->orderByPivot('updated_at', 'desc')->withCardStats()->paginate(30),
            'lists' => $user->filmLists()
                ->with(['user', 'films' => fn ($query) => $query->orderBy('list_items.position')->limit(5)])
                ->withCount('films')
                ->unless($isOwner, fn ($query) => $query->public())
                ->latest()
                ->paginate(12),
            default => $user->diaryEntries()->with('film')->latest('watched_on')->latest('id')->paginate(20),
        };

        return view('profile.show', $data + ['items' => $items->withQueryString()]);
    }
}
