<?php

namespace Tests\Feature;

use App\Livewire\DiaryLogger;
use App\Livewire\FollowButton;
use App\Livewire\ReviewVote;
use App\Livewire\WatchlistToggle;
use App\Models\Activity;
use App\Models\Film;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FilmInteractionTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_can_add_a_film_to_their_watchlist(): void
    {
        $user = User::factory()->create();
        $film = Film::factory()->create();

        Livewire::actingAs($user)->test(WatchlistToggle::class, ['film' => $film])
            ->call('toggle')
            ->assertSet('inList', true);

        $this->assertDatabaseHas('watchlists', ['user_id' => $user->id, 'film_id' => $film->id]);
    }

    public function test_resaving_an_unchanged_review_does_not_log_duplicate_activity(): void
    {
        $user = User::factory()->create();
        $film = Film::factory()->create();
        $review = ['rating' => 4, 'body' => 'Great.'];

        $this->actingAs($user)->post(route('films.review', $film), $review);
        $this->actingAs($user)->post(route('films.review', $film), $review);

        $this->assertSame(1, Activity::where('type', 'reviewed')->count());
    }

    public function test_diary_entries_without_a_rating_do_not_create_reviews(): void
    {
        $user = User::factory()->create();
        $film = Film::factory()->create();

        Livewire::actingAs($user)->test(DiaryLogger::class, ['film' => $film])
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('diary_entries', ['user_id' => $user->id, 'film_id' => $film->id]);
        $this->assertDatabaseMissing('reviews', ['user_id' => $user->id, 'film_id' => $film->id]);
    }

    public function test_logging_a_watch_resets_the_form_and_keeps_the_review_text(): void
    {
        $user = User::factory()->create();
        $film = Film::factory()->create();
        Review::factory()->create(['user_id' => $user->id, 'film_id' => $film->id, 'rating' => 3, 'body' => 'Original thoughts.']);

        Livewire::actingAs($user)->test(DiaryLogger::class, ['film' => $film])
            ->assertSet('rating', 3.0)
            ->set('rating', 4.5)
            ->call('save')
            ->assertSet('review', '')
            ->assertSet('rewatch', true)
            ->assertSet('showForm', false);

        $review = Review::where('user_id', $user->id)->where('film_id', $film->id)->firstOrFail();
        $this->assertSame(4.5, $review->rating);
        $this->assertSame('Original thoughts.', $review->body);
    }

    public function test_diary_tags_are_attached_to_the_film(): void
    {
        $user = User::factory()->create();
        $film = Film::factory()->create();

        Livewire::actingAs($user)->test(DiaryLogger::class, ['film' => $film])
            ->set('newTag', 'Slow Burn')
            ->call('addTag')
            ->call('save');

        $this->assertSame(['slow-burn'], $film->tags()->pluck('slug')->all());
    }

    public function test_tags_without_letters_or_numbers_are_rejected(): void
    {
        $user = User::factory()->create();
        $film = Film::factory()->create();

        Livewire::actingAs($user)->test(DiaryLogger::class, ['film' => $film])
            ->set('newTag', '!!!')
            ->call('addTag')
            ->assertHasErrors('newTag')
            ->assertSet('tags', []);
    }

    public function test_members_can_agree_switch_to_disagree_and_undo(): void
    {
        $voter = User::factory()->create();
        $review = Review::factory()->for(User::factory())->for(Film::factory())->create();

        $component = Livewire::actingAs($voter)->test(ReviewVote::class, ['review' => $review])
            ->call('agree')
            ->assertSet('vote', 1)
            ->assertSet('agrees', 1);

        $component->call('disagree')
            ->assertSet('vote', -1)
            ->assertSet('agrees', 0)
            ->assertSet('disagrees', 1);

        $component->call('disagree')->assertSet('vote', 0)->assertSet('disagrees', 0);

        $this->assertSame(0, $review->voters()->count());
    }

    public function test_a_stale_agree_click_does_not_double_count(): void
    {
        $voter = User::factory()->create();
        $review = Review::factory()->for(User::factory())->for(Film::factory())->create();

        Livewire::actingAs($voter)->test(ReviewVote::class, ['review' => $review])->call('agree');
        Livewire::actingAs($voter)->test(ReviewVote::class, ['review' => $review])->call('agree');
        Livewire::actingAs(User::factory()->create())->test(ReviewVote::class, ['review' => $review])->call('agree');

        $this->assertSame(1, $review->fresh()->agrees);
    }

    public function test_authors_can_not_vote_on_their_own_review(): void
    {
        $author = User::factory()->create();
        $review = Review::factory()->for($author)->for(Film::factory())->create();

        Livewire::actingAs($author)->test(ReviewVote::class, ['review' => $review])
            ->call('agree')
            ->assertSet('vote', 0);

        $this->assertSame(0, $review->fresh()->agrees);
    }

    public function test_a_stale_follow_click_does_not_fail(): void
    {
        $user = User::factory()->create();
        $target = User::factory()->create();
        $user->following()->attach($target);

        Livewire::actingAs($user)->test(FollowButton::class, ['user' => $target])
            ->set('following', false)
            ->call('toggle')
            ->assertSet('followersCount', 1);

        $this->assertSame(1, $target->followers()->count());
    }
}
