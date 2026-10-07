<?php

namespace App\Http\Controllers;

use App\Models\Review;
use Illuminate\View\View;

class JournalController extends Controller
{
    /**
     * The community journal: every written review, newest first, grouped by month.
     */
    public function index(): View
    {
        $reviews = Review::with(['user', 'film'])
            ->whereNotNull('body')
            ->where('body', '!=', '')
            ->latest()
            ->paginate(20);

        return view('journal.index', [
            'reviews' => $reviews,
            'months' => $reviews->getCollection()->groupBy(fn (Review $review) => $review->created_at->format('F Y')),
        ]);
    }
}
