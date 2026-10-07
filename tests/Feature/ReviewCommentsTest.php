<?php

namespace Tests\Feature;

use App\Models\Film;
use App\Models\Review;
use App\Models\ReviewComment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewCommentsTest extends TestCase
{
    use RefreshDatabase;

    private function review(): Review
    {
        return Review::factory()->for(User::factory())->for(Film::factory())->create(['body' => 'A fine film.']);
    }

    public function test_review_page_shows_the_review_and_comments(): void
    {
        $review = $this->review();
        ReviewComment::factory()->for($review)->create(['body' => 'Totally agree!']);

        $this->get(route('reviews.show', $review))
            ->assertOk()
            ->assertSee('A fine film.')
            ->assertSee('Totally agree!');
    }

    public function test_members_can_comment_on_reviews(): void
    {
        $review = $this->review();
        $commenter = User::factory()->create();

        $this->actingAs($commenter)
            ->post(route('reviews.comments.store', $review), ['body' => 'Nice take.'])
            ->assertRedirect(route('reviews.show', $review).'#comments');

        $this->assertDatabaseHas('review_comments', ['review_id' => $review->id, 'user_id' => $commenter->id, 'body' => 'Nice take.']);
    }

    public function test_guests_can_not_comment(): void
    {
        $review = $this->review();

        $this->post(route('reviews.comments.store', $review), ['body' => 'Hello'])->assertRedirect(route('login'));
        $this->assertSame(0, ReviewComment::count());
    }

    public function test_comment_author_and_review_author_can_delete_but_strangers_can_not(): void
    {
        $review = $this->review();
        $first = ReviewComment::factory()->for($review)->create();
        $second = ReviewComment::factory()->for($review)->create();

        $this->actingAs(User::factory()->create())->delete(route('comments.destroy', $first))->assertForbidden();

        $this->actingAs($first->user)->delete(route('comments.destroy', $first))->assertRedirect();
        $this->actingAs($review->user)->delete(route('comments.destroy', $second))->assertRedirect();

        $this->assertSame(0, ReviewComment::count());
    }
}
