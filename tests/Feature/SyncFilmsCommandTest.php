<?php

namespace Tests\Feature;

use App\Models\Film;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use Tests\TestCase;

class SyncFilmsCommandTest extends TestCase
{
    use RefreshDatabase;

    private const API = 'https://api.themoviedb.org/3';

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.tmdb.key' => 'test-key', 'services.tmdb.base_url' => self::API]);
    }

    public function test_it_imports_trending_and_upcoming_films_with_trailers(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            self::API.'/trending/movie/week*' => Http::response(['results' => [['id' => 101]]]),
            self::API.'/movie/upcoming*' => Http::response(['results' => [['id' => 202]]]),
            self::API.'/movie/101*' => Http::response([
                'title' => 'Trending Film',
                'release_date' => '2024-03-01',
                'genres' => [['name' => 'Drama']],
                'videos' => ['results' => []],
            ]),
            self::API.'/movie/202*' => Http::response([
                'title' => 'Upcoming Film',
                'release_date' => '',
                'videos' => ['results' => [
                    ['site' => 'YouTube', 'type' => 'Featurette', 'key' => 'wrong', 'official' => true],
                    ['site' => 'YouTube', 'type' => 'Trailer', 'key' => 'abc123', 'official' => true],
                ]],
            ]),
        ]);

        $this->artisan('films:sync')->assertSuccessful();

        $trending = Film::where('tmdb_id', 101)->firstOrFail();
        $this->assertSame('Trending Film', $trending->title);
        $this->assertSame(2024, (int) $trending->year);
        $this->assertFalse($trending->is_teaser);

        $upcoming = Film::where('tmdb_id', 202)->firstOrFail();
        $this->assertTrue($upcoming->is_teaser);
        $this->assertNull($upcoming->release_date);
        $this->assertSame('abc123', $upcoming->trailer_key);
    }

    public function test_it_fails_cleanly_when_tmdb_is_unavailable(): void
    {
        Sleep::fake();
        Http::preventStrayRequests();
        Http::fake([self::API.'/trending/movie/week*' => Http::response([], 503)]);

        $this->artisan('films:sync')
            ->expectsOutputToContain('Could not reach TMDB')
            ->assertFailed();

        $this->assertSame(0, Film::count());
    }

    public function test_it_requires_an_api_key(): void
    {
        config(['services.tmdb.key' => null]);
        Http::preventStrayRequests();

        $this->artisan('films:sync')->assertFailed();
    }

    public function test_film_pages_never_call_tmdb(): void
    {
        Http::preventStrayRequests();

        $this->get(route('films.index'))->assertOk();
        $this->get(route('teasers.index'))->assertOk();
    }
}
