<?php

namespace App\Livewire;

use App\Models\Activity;
use App\Models\Review;
use Livewire\Component;

/**
 * Agree / disagree buttons on a review.
 */
class ReviewVote extends Component
{
    public Review $review;

    public int $agrees = 0;

    public int $disagrees = 0;

    /**
     * The current member's vote: 1 agree, -1 disagree, 0 none.
     */
    public int $vote = 0;

    public function mount(Review $review): void
    {
        $this->review = $review;
        // A review created in this request hasn't picked up the column defaults yet.
        $this->agrees = (int) $review->agrees;
        $this->disagrees = (int) $review->disagrees;
        $this->vote = $review->voteBy(auth()->user());
    }

    public function agree(): void
    {
        $this->cast(Review::AGREE);
    }

    public function disagree(): void
    {
        $this->cast(Review::DISAGREE);
    }

    protected function cast(int $vote): void
    {
        if (! auth()->check()) {
            $this->redirect(route('login'));

            return;
        }

        if ($this->review->user_id === auth()->id()) {
            $this->dispatch('notify', message: "You can't vote on your own review.", type: 'error');

            return;
        }

        $this->vote = $this->review->castVote(auth()->user(), $vote);
        $this->agrees = (int) $this->review->agrees;
        $this->disagrees = (int) $this->review->disagrees;

        if ($this->vote === Review::AGREE) {
            Activity::log(auth()->user(), 'agreed_review', $this->review, [
                'film_title' => $this->review->film->title ?? '',
            ]);
        }
    }

    public function render()
    {
        return view('livewire.review-vote');
    }
}
