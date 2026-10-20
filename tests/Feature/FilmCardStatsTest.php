<?php

namespace Tests\Feature;

use App\Livewire\FilmCardStats;
use App\Models\Film;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FilmCardStatsTest extends TestCase
{
    use RefreshDatabase;

    public function test_tapping_a_star_saves_the_rating_marks_the_film_watched_and_updates_the_counts(): void
    {
        $member = User::factory()->create();
        $film = Film::factory()->create();

        Livewire::actingAs($member)->test(FilmCardStats::class, ['film' => $film])
            ->call('rate', 4.5)
            ->assertSet('rating', 4.5)
            ->assertSet('average', 4.5)
            ->assertSet('views', 1);

        $this->assertSame(4.5, Review::where('user_id', $member->id)->where('film_id', $film->id)->sole()->rating);
        $this->assertTrue($member->watchedFilms()->whereKey($film->id)->exists());
    }

    public function test_guests_tapping_a_star_are_sent_to_sign_in_and_nothing_is_saved(): void
    {
        $film = Film::factory()->create();

        Livewire::test(FilmCardStats::class, ['film' => $film])
            ->call('rate', 4)
            ->assertRedirect(route('login'));

        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_a_rating_that_is_not_a_half_star_step_is_ignored(): void
    {
        $member = User::factory()->create();
        $film = Film::factory()->create();

        Livewire::actingAs($member)->test(FilmCardStats::class, ['film' => $film])
            ->call('rate', 4.2)
            ->assertSet('rating', 0.0);

        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_cards_show_the_members_own_rating_and_the_films_view_and_heart_counts(): void
    {
        [$member, $fan, $otherFan] = User::factory(3)->create();
        $film = Film::factory()->create();
        Review::factory()->create(['user_id' => $member->id, 'film_id' => $film->id, 'rating' => 2]);
        Review::factory()->create(['user_id' => $fan->id, 'film_id' => $film->id, 'rating' => 5]);
        $member->markWatched($film);
        $fan->markWatched($film);
        $otherFan->markWatched($film);
        $fan->films()->updateExistingPivot($film->id, ['liked' => true]);

        $response = $this->actingAs($member)->get(route('films.index'));

        $response->assertSee('Your rating: 2 stars. Tap to change it.')
            ->assertSee('title="Watched by 3 members"', false)
            ->assertSee('title="Liked by 1 member"', false);
    }

    public function test_cards_show_the_average_rating_to_members_who_have_not_rated_the_film(): void
    {
        $fan = User::factory()->create();
        $film = Film::factory()->create();
        Review::factory()->create(['user_id' => $fan->id, 'film_id' => $film->id, 'rating' => 4]);

        $response = $this->get(route('films.index'));

        $response->assertSee('Average 4.0 stars. Tap to rate.');
    }
}
