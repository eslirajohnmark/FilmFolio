<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Models\ReviewComment;
use Illuminate\Http\RedirectResponse;

/**
 * Removing inappropriate member content. Reachable from the admin panel and,
 * while an admin is signed in, from the "Remove (admin)" buttons on the site.
 */
class ModerationController extends Controller
{
    public function destroyReview(Review $review): RedirectResponse
    {
        $film = $review->film;
        $review->delete();

        $message = $review->body ? 'Review removed.' : 'Rating removed.';

        if (url()->previous() === route('reviews.show', $review)) {
            return redirect()->route('films.show', $film)->with('status', $message);
        }

        return back()->with('status', $message);
    }

    public function destroyComment(ReviewComment $comment): RedirectResponse
    {
        $comment->delete();

        return back()->with('status', 'Comment removed.');
    }
}
