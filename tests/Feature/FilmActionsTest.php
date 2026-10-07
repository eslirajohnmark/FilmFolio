<?php

namespace Tests\Feature;

use App\Livewire\FilmActions;
use App\Models\Activity;
use App\Models\Film;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FilmActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_watching_a_film_takes_it_off_the_watchlist(): void
    {
        $user = User::factory()->create();
        $film = Film::factory()->create();
        $user->watchlist()->attach($film);

        Livewire::actingAs($user)->test(FilmActions::class, ['film' => $film])
            ->assertSet('inWatchlist', true)
            ->call('toggleWatched')
            ->assertSet('watched', true)
            ->assertSet('inWatchlist', false);

        $this->assertTrue($user->watchedFilms()->whereKey($film->id)->exists());
    }

    public function test_liking_a_film_marks_it_watched_and_logs_activity(): void
    {
        $user = User::factory()->create();
        $film = Film::factory()->create();

        Livewire::actingAs($user)->test(FilmActions::class, ['film' => $film])
            ->call('toggleLiked')
            ->assertSet('liked', true)
            ->assertSet('watched', true)
            ->call('toggleLiked')
            ->assertSet('liked', false)
            ->assertSet('watched', true);

        $this->assertSame(1, Activity::where('type', 'liked_film')->count());
    }

    public function test_quick_rating_creates_a_rating_and_can_be_removed(): void
    {
        $user = User::factory()->create();
        $film = Film::factory()->create();

        $component = Livewire::actingAs($user)->test(FilmActions::class, ['film' => $film])
            ->call('rate', 3.5)
            ->assertSet('rating', 3.5)
            ->assertSet('watched', true);

        $this->assertSame(3.5, Review::where('user_id', $user->id)->sole()->rating);

        $component->call('clearRating')->assertSet('rating', 0.0);
        $this->assertSame(0, Review::count());
    }

    public function test_invalid_ratings_are_ignored(): void
    {
        $user = User::factory()->create();
        $film = Film::factory()->create();

        Livewire::actingAs($user)->test(FilmActions::class, ['film' => $film])
            ->call('rate', 3.3)
            ->call('rate', 6)
            ->assertSet('rating', 0.0);

        $this->assertSame(0, Review::count());
    }

    public function test_removing_a_rating_keeps_a_written_review(): void
    {
        $user = User::factory()->create();
        $film = Film::factory()->create();
        Review::factory()->for($user)->for($film)->create(['rating' => 4, 'body' => 'Keep me.']);

        Livewire::actingAs($user)->test(FilmActions::class, ['film' => $film])->call('clearRating');

        $this->assertSame('Keep me.', Review::sole()->body);
    }

    public function test_members_can_have_at_most_four_favourites(): void
    {
        $user = User::factory()->create();
        $user->favoriteFilms()->attach(Film::factory(4)->create()->pluck('id'));
        $fifth = Film::factory()->create();

        Livewire::actingAs($user)->test(FilmActions::class, ['film' => $fifth])
            ->call('toggleFavorite')
            ->assertSet('isFavorite', false);

        $this->assertSame(4, $user->favoriteFilms()->count());
    }

    public function test_guests_are_sent_to_login(): void
    {
        $film = Film::factory()->create();

        Livewire::test(FilmActions::class, ['film' => $film])
            ->call('toggleWatched')
            ->assertRedirect(route('login'));
    }
}
