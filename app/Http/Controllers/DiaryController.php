<?php

namespace App\Http\Controllers;

use App\Models\DiaryEntry;
use Illuminate\Http\Request;

class DiaryController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $entries = DiaryEntry::with(['film', 'tags'])
            ->where('user_id', $user->id)
            ->when($request->year, fn ($q) => $q->whereYear('watched_on', $request->year))
            ->when($request->month, fn ($q) => $q->whereMonth('watched_on', $request->month))
            ->orderByDesc('watched_on')
            ->paginate(30);

        $stats = [
            'total' => DiaryEntry::where('user_id', $user->id)->count(),
            'this_year' => DiaryEntry::where('user_id', $user->id)
                ->whereYear('watched_on', now()->year)->count(),
            'rewatches' => DiaryEntry::where('user_id', $user->id)
                ->where('rewatch', true)->count(),
            'avg_rating' => round(
                DiaryEntry::where('user_id', $user->id)->avg('rating') ?? 0,
                2
            ),
        ];

        return view('diary.index', compact('entries', 'stats'));
    }
}
