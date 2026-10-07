<?php

namespace Tests\Feature;

use App\Models\DiaryEntry;
use App\Models\Film;
use App\Models\FilmList;
use App\Models\Review;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PageRenderTest extends TestCase
{
    use RefreshDatabase;

    public function test_watchlist_page_lists_the_users_films(): void
    {
        $user = User::factory()->create();
        $user->watchlist()->attach(Film::factory()->create(['title' => 'Must See']));

        $this->get(route('watchlist.index'))->assertRedirect(route('login'));
        $this->actingAs($user)->get(route('watchlist.index'))->assertOk()->assertSee('Must See');
    }

    public function test_journal_shows_written_reviews_grouped_by_month(): void
    {
        $film = Film::factory()->create();
        Review::factory()->for(User::factory())->for($film)->create(['body' => 'Short and sweet.', 'created_at' => '2026-11-15']);
        Review::factory()->for(User::factory())->for($film)->create(['body' => 'Rating only', 'created_at' => '2026-10-02'])
            ->forceFill(['body' => null])->save();

        $this->get(route('journal.index'))
            ->assertOk()
            ->assertSee('November 2026')
            ->assertSee('Short and sweet.')
            ->assertDontSee('October 2026');
    }

    public function test_film_page_shows_reviews_and_average_rating(): void
    {
        $film = Film::factory()->create();
        Review::factory()->for(User::factory())->for($film)->create(['rating' => 4, 'body' => 'Loved it.']);
        Review::factory()->for(User::factory())->for($film)->create(['rating' => 3, 'body' => 'Pretty good.']);

        $this->get(route('films.show', $film))
            ->assertOk()
            ->assertSee('<div class="avg">3.5</div>', false)
            ->assertSee('Loved it.')
            ->assertSee('Pretty good.');
    }

    public function test_profile_tabs_show_diary_reviews_and_only_public_lists(): void
    {
        $user = User::factory()->create(['bio' => null]);
        $film = Film::factory()->create(['title' => 'Logged Film']);
        DiaryEntry::create(['user_id' => $user->id, 'film_id' => $film->id, 'watched_on' => now()]);
        Review::factory()->create(['user_id' => $user->id, 'film_id' => $film->id, 'body' => 'My review text']);
        FilmList::create(['user_id' => $user->id, 'name' => 'Public Picks', 'visibility' => 'public']);
        FilmList::create(['user_id' => $user->id, 'name' => 'Secret Stash', 'visibility' => 'private']);

        $this->get(route('profile.show', $user))->assertOk()->assertSee('Logged Film')
            ->assertDontSee('Film-lover, reviewer, curator.');
        $this->get(route('profile.show', [$user, 'tab' => 'reviews']))->assertSee('My review text');
        $this->get(route('profile.show', [$user, 'tab' => 'lists']))
            ->assertSee('Public Picks')
            ->assertDontSee('Secret Stash');
        $this->actingAs($user)->get(route('profile.show', [$user, 'tab' => 'lists']))->assertSee('Secret Stash');
    }

    public function test_tag_page_lists_tagged_films(): void
    {
        $film = Film::factory()->create(['title' => 'Moody Noir']);
        $tag = Tag::findOrCreate('noir');
        $film->tags()->attach($tag);

        $this->get(route('tags.show', $tag))->assertOk()->assertSee('Moody Noir');
    }

    public function test_teasers_page_embeds_the_trailer_with_the_film_description(): void
    {
        Film::factory()->create([
            'title' => 'Featured One',
            'synopsis' => 'A thrilling description.',
            'is_teaser' => true,
            'trailer_key' => 'abc123defgh',
        ]);

        $this->get(route('teasers.index'))
            ->assertOk()
            ->assertSee('Featured One')
            ->assertSee('A thrilling description.')
            ->assertSee('https://www.youtube-nocookie.com/embed/abc123defgh', false);
    }

    public function test_choosing_an_upcoming_film_plays_its_trailer(): void
    {
        Film::factory()->create(['title' => 'First Up', 'is_teaser' => true, 'trailer_key' => 'aaaaaaaaaaa']);
        $second = Film::factory()->create(['title' => 'Second Up', 'is_teaser' => true, 'trailer_key' => 'bbbbbbbbbbb']);

        $this->get(route('teasers.index', ['film' => $second->id]))
            ->assertSee('youtube-nocookie.com/embed/bbbbbbbbbbb', false)
            ->assertDontSee('youtube-nocookie.com/embed/aaaaaaaaaaa', false);
    }

    public function test_guest_home_page_shows_films_not_a_fake_profile(): void
    {
        Film::factory()->create(['title' => 'Fresh Release']);

        $this->get('/')->assertOk()->assertSee('Fresh Release')->assertDontSee('Sci-Fi Favorites');
    }
}
