<?php

namespace Tests\Feature;

use App\Livewire\FilmSearch;
use App\Models\Film;
use App\Models\InterestSignal;
use App\Models\Review;
use App\Models\User;
use App\Services\Recommendations;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RecommendationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_searching_for_horror_suggests_horror_films(): void
    {
        $member = User::factory()->create();
        Film::factory()->create(['title' => 'The Haunting Hour', 'genre' => 'Horror']);
        Film::factory()->create(['title' => 'Laugh Riot', 'genre' => 'Comedy']);

        $this->actingAs($member)->get(route('search', ['q' => 'horror']))
            ->assertOk()
            ->assertSee('Suggested for you')
            ->assertSee('The Haunting Hour')
            ->assertDontSee('Laugh Riot');

        $this->actingAs($member)->get(route('films.index'))->assertSee('The Haunting Hour');
        $this->actingAs($member)->get(route('home'))->assertSee('Suggested for you');
    }

    public function test_everyday_words_count_towards_a_genre(): void
    {
        Film::factory()->create(['genre' => 'Horror']);
        Film::factory()->create(['genre' => 'Comedy']);

        $recommendations = app(Recommendations::class);

        $this->assertSame(['Horror'], $recommendations->genresMentionedIn('something scary'));
        $this->assertSame(['Comedy'], $recommendations->genresMentionedIn('a funny film'));
        $this->assertSame([], $recommendations->genresMentionedIn('paris'));
    }

    public function test_typing_into_the_film_filter_records_one_signal_with_the_full_word(): void
    {
        $member = User::factory()->create();
        Film::factory()->create(['genre' => 'Horror']);

        Livewire::actingAs($member)->test(FilmSearch::class)
            ->set('q', 'horr')
            ->set('q', 'horro')
            ->set('q', 'horror');

        $signals = InterestSignal::where('user_id', $member->id)->where('genre', 'Horror')->get();
        $this->assertCount(1, $signals);
        $this->assertSame('horror', $signals->first()->term);
    }

    public function test_watched_films_are_not_suggested_and_low_ratings_count_against_a_genre(): void
    {
        $member = User::factory()->create();
        $seen = Film::factory()->create(['title' => 'Already Seen', 'genre' => 'Horror']);
        Film::factory()->create(['title' => 'Fresh Fright', 'genre' => 'Horror']);
        $disliked = Film::factory()->create(['genre' => 'Comedy']);
        Film::factory()->create(['title' => 'Another Comedy', 'genre' => 'Comedy']);

        $member->markWatched($seen);
        Review::factory()->for($member)->for($seen)->create(['rating' => 5]);
        Review::factory()->for($member)->for($disliked)->create(['rating' => 1]);

        $suggestions = app(Recommendations::class)->suggestionsFor($member);

        $this->assertSame(['Fresh Fright'], $suggestions['films']->pluck('title')->all());
        $this->assertSame('Horror', $suggestions['genres'][0]);
    }

    public function test_nothing_is_suggested_without_any_interest(): void
    {
        Film::factory()->create(['title' => 'Lonely Film']);

        $this->actingAs(User::factory()->create())->get(route('films.index'))
            ->assertOk()
            ->assertDontSee('Suggested for you');
    }

    public function test_a_guests_searches_follow_them_when_they_sign_in(): void
    {
        $member = User::factory()->create();
        InterestSignal::create(['session_key' => 'guest-session', 'genre' => 'Horror', 'source' => 'search', 'term' => 'horror', 'weight' => 4]);
        InterestSignal::create(['session_key' => 'someone-else', 'genre' => 'Comedy', 'source' => 'search', 'weight' => 4]);

        app(Recommendations::class)->claimGuestSignals($member, 'guest-session');

        $this->assertSame(['Horror'], InterestSignal::where('user_id', $member->id)->pluck('genre')->all());
        $this->assertSame(1, InterestSignal::whereNull('user_id')->count());
    }
}
