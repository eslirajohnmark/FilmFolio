<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Review;
use App\Models\ReviewComment;
use App\Notifications\NewReviewComment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ReviewController extends Controller
{
    public function show(Review $review): View
    {
        $review->load(['user', 'film', 'comments.user'])->loadCount('comments');

        return view('reviews.show', [
            'review' => $review,
            'moreFromFilm' => Review::with(['user', 'film'])
                ->where('film_id', $review->film_id)
                ->whereKeyNot($review->id)
                ->whereNotNull('body')
                ->orderByDesc('agrees')
                ->take(4)
                ->get(),
        ]);
    }

    public function storeComment(Request $request, Review $review): RedirectResponse
    {
        $data = $request->validate(['body' => 'required|string|min:2|max:2000']);

        $comment = $review->comments()->create([
            'user_id' => $request->user()->id,
            'body' => $data['body'],
        ]);

        if ($review->user_id !== $request->user()->id) {
            Activity::log($request->user(), 'commented', $review, ['film_title' => $review->film->title]);
            $review->user->notify(new NewReviewComment($comment));
        }

        return redirect()->to(route('reviews.show', $review).'#comments')->with('status', 'Comment posted.');
    }

    /**
     * Delete a review (or a bare rating). Authors delete their own; admins moderate.
     */
    public function destroy(Request $request, Review $review): RedirectResponse
    {
        Gate::authorize('delete', $review);

        $film = $review->film;
        $review->delete();

        $message = $review->body ? 'Review deleted.' : 'Rating removed.';

        // Don't send people back to the page of the review that no longer exists.
        if (url()->previous() === route('reviews.show', $review)) {
            return redirect()->route('films.show', $film)->with('status', $message);
        }

        return back()->with('status', $message);
    }

    public function destroyComment(ReviewComment $comment): RedirectResponse
    {
        Gate::authorize('delete', $comment);

        $comment->delete();

        return back()->with('status', 'Comment deleted.');
    }
}
