<?php

namespace Tests\Feature\Admin;

use App\Models\Film;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AdminFilmsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_admins_can_add_a_teaser_with_a_youtube_trailer(): void
    {
        $this->actingAs($this->admin())->post(route('admin.films.store'), [
            'title' => 'Future Film',
            'year' => now()->year + 1,
            'genre' => 'Sci-Fi',
            'synopsis' => 'Coming soon.',
            'trailer' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ&t=10',
            'is_teaser' => '1',
        ])->assertSessionHasNoErrors();

        $film = Film::where('title', 'Future Film')->sole();
        $this->assertTrue($film->is_teaser);
        $this->assertSame('dQw4w9WgXcQ', $film->trailer_key);
    }

    public function test_invalid_trailer_links_are_rejected(): void
    {
        $this->actingAs($this->admin())->post(route('admin.films.store'), [
            'title' => 'Bad Trailer',
            'year' => 2024,
            'trailer' => 'https://vimeo.com/123',
        ])->assertSessionHasErrors('trailer');
    }

    public function test_admins_can_edit_toggle_teaser_and_delete_films(): void
    {
        $admin = $this->admin();
        $film = Film::factory()->create(['title' => 'Old Title', 'year' => 2001, 'is_teaser' => false]);

        $this->actingAs($admin)->put(route('admin.films.update', $film), ['title' => 'New Title', 'year' => 2002])
            ->assertRedirect(route('admin.films.edit', $film));
        $this->assertSame('New Title', $film->fresh()->title);

        $this->actingAs($admin)->patch(route('admin.films.teaser', $film));
        $this->assertTrue($film->fresh()->is_teaser);

        $this->actingAs($admin)->delete(route('admin.films.destroy', $film));
        $this->assertModelMissing($film);
    }

    public function test_admins_can_import_a_film_from_tmdb(): void
    {
        config(['services.tmdb.key' => 'test-key', 'services.tmdb.base_url' => 'https://api.themoviedb.org/3']);
        Http::preventStrayRequests();
        Http::fake(['https://api.themoviedb.org/3/movie/42*' => Http::response([
            'title' => 'Imported Film',
            'release_date' => '2027-01-01',
            'videos' => ['results' => []],
        ])]);

        $this->actingAs($this->admin())->post(route('admin.films.import'), ['tmdb_id' => 42, 'is_teaser' => '1']);

        $this->assertTrue(Film::where('tmdb_id', 42)->sole()->is_teaser);
    }

    public function test_admin_film_pages_render(): void
    {
        $admin = $this->admin();
        $film = Film::factory()->create();

        $this->actingAs($admin)->get(route('admin.films.index'))->assertOk()->assertSee($film->title);
        $this->actingAs($admin)->get(route('admin.films.create'))->assertOk();
        $this->actingAs($admin)->get(route('admin.films.edit', $film))->assertOk();
    }
}
