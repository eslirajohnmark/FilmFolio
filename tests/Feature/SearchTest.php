<?php

namespace Tests\Feature;

use App\Models\Film;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use Tests\TestCase;

class SearchTest extends TestCase
{
    use RefreshDatabase;

    private const API = 'https://api.themoviedb.org/3';

    public function test_search_finds_local_films_and_members_without_tmdb(): void
    {
        config(['services.tmdb.key' => null]);
        Http::preventStrayRequests();
        Film::factory()->create(['title' => 'Blade Runner']);
        User::factory()->create(['username' => 'bladefan']);

        $this->get(route('search', ['q' => 'blade']))
            ->assertOk()
            ->assertSee('Blade Runner')
            ->assertSee('bladefan');
    }

    public function test_search_offers_tmdb_films_that_are_not_imported_yet(): void
    {
        config(['services.tmdb.key' => 'test-key', 'services.tmdb.base_url' => self::API]);
        Http::preventStrayRequests();
        Http::fake([self::API.'/search/movie*' => Http::response(['results' => [
            ['id' => 555, 'title' => 'Paris, Texas', 'release_date' => '1984-05-19', 'poster_path' => '/p.jpg'],
        ]])]);

        $this->get(route('search', ['q' => 'paris']))
            ->assertOk()
            ->assertSeeInOrder(['Paris, Texas', '1984'])
            ->assertSee(route('films.tmdb', 555), false);
    }

    public function test_search_still_works_when_tmdb_is_down(): void
    {
        Sleep::fake();
        config(['services.tmdb.key' => 'test-key', 'services.tmdb.base_url' => self::API]);
        Http::preventStrayRequests();
        Http::fake([self::API.'/search/movie*' => Http::response([], 500)]);
        Film::factory()->create(['title' => 'Paris Blues']);

        $this->get(route('search', ['q' => 'paris']))->assertOk()->assertSee('Paris Blues');
    }

    public function test_opening_a_tmdb_result_imports_it(): void
    {
        config(['services.tmdb.key' => 'test-key', 'services.tmdb.base_url' => self::API]);
        Http::preventStrayRequests();
        Http::fake([self::API.'/movie/555*' => Http::response([
            'title' => 'Paris, Texas',
            'release_date' => '1984-05-19',
            'videos' => ['results' => []],
        ])]);

        $response = $this->get(route('films.tmdb', 555));

        $film = Film::where('tmdb_id', 555)->sole();
        $response->assertRedirect(route('films.show', $film));
    }

    public function test_opening_an_imported_film_does_not_call_tmdb(): void
    {
        Http::preventStrayRequests();
        $film = Film::factory()->create(['tmdb_id' => 777]);

        $this->get(route('films.tmdb', 777))->assertRedirect(route('films.show', $film));
    }
}
