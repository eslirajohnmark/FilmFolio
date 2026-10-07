<?php

namespace Tests\Feature\Admin;

use App\Models\Film;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FilmMediaUploadTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function video(string $name = 'clip.mp4'): UploadedFile
    {
        return UploadedFile::fake()->create($name, 2048, 'video/mp4');
    }

    public function test_admins_can_add_a_film_with_uploaded_poster_trailer_and_teaser(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin())->post(route('admin.films.store'), [
            'title' => 'Kuruko Josh Basketball',
            'year' => 2026,
            'genre' => 'Action',
            'is_teaser' => '1',
            'poster_file' => UploadedFile::fake()->image('poster.jpg', 400, 600),
            'backdrop_file' => UploadedFile::fake()->image('backdrop.png', 1280, 720),
            'trailer_file' => $this->video('trailer.mp4'),
            'teaser_file' => $this->video('teaser.webm'),
        ])->assertSessionHasNoErrors();

        $film = Film::where('title', 'Kuruko Josh Basketball')->sole();

        foreach (['poster_path', 'backdrop_path', 'trailer_path', 'teaser_path'] as $column) {
            $this->assertTrue(Film::isUpload($film->{$column}), "{$column} should be an upload");
            Storage::disk('public')->assertExists($film->{$column});
        }

        $this->assertStringContainsString('/storage/films/posters/', $film->posterUrl());
        $this->assertStringContainsString('/storage/films/trailers/', $film->trailerVideoUrl());
        $this->assertTrue($film->hasTrailer());
    }

    public function test_uploading_a_new_poster_replaces_and_deletes_the_old_file(): void
    {
        Storage::fake('public');
        $admin = $this->admin();
        $this->actingAs($admin)->post(route('admin.films.store'), [
            'title' => 'Replace Me', 'year' => 2024,
            'poster_file' => UploadedFile::fake()->image('old.jpg'),
        ]);
        $film = Film::where('title', 'Replace Me')->sole();
        $oldPoster = $film->poster_path;

        $this->actingAs($admin)->put(route('admin.films.update', $film), [
            'title' => 'Replace Me', 'year' => 2024,
            'poster_file' => UploadedFile::fake()->image('new.jpg'),
        ])->assertSessionHasNoErrors();

        $film->refresh();
        $this->assertNotSame($oldPoster, $film->poster_path);
        Storage::disk('public')->assertMissing($oldPoster);
        Storage::disk('public')->assertExists($film->poster_path);
    }

    public function test_saving_without_new_files_keeps_existing_uploads(): void
    {
        Storage::fake('public');
        $admin = $this->admin();
        $this->actingAs($admin)->post(route('admin.films.store'), [
            'title' => 'Keep Me', 'year' => 2024,
            'poster_file' => UploadedFile::fake()->image('keep.jpg'),
            'trailer_file' => $this->video(),
        ]);
        $film = Film::where('title', 'Keep Me')->sole();
        $poster = $film->poster_path;
        $trailer = $film->trailer_path;

        $this->actingAs($admin)->put(route('admin.films.update', $film), [
            'title' => 'Keep Me (Renamed)', 'year' => 2024, 'poster_path' => '',
        ])->assertSessionHasNoErrors();

        $film->refresh();
        $this->assertSame($poster, $film->poster_path);
        $this->assertSame($trailer, $film->trailer_path);
        Storage::disk('public')->assertExists([$poster, $trailer]);
    }

    public function test_remove_checkboxes_delete_uploaded_files(): void
    {
        Storage::fake('public');
        $admin = $this->admin();
        $this->actingAs($admin)->post(route('admin.films.store'), [
            'title' => 'Remove Me', 'year' => 2024,
            'poster_file' => UploadedFile::fake()->image('p.jpg'),
            'teaser_file' => $this->video(),
        ]);
        $film = Film::where('title', 'Remove Me')->sole();
        $files = [$film->poster_path, $film->teaser_path];

        $this->actingAs($admin)->put(route('admin.films.update', $film), [
            'title' => 'Remove Me', 'year' => 2024,
            'remove_poster' => '1', 'remove_teaser_video' => '1',
        ]);

        $film->refresh();
        $this->assertNull($film->poster_path);
        $this->assertNull($film->teaser_path);
        Storage::disk('public')->assertMissing($files);
    }

    public function test_a_typed_poster_url_replaces_an_uploaded_poster(): void
    {
        Storage::fake('public');
        $admin = $this->admin();
        $this->actingAs($admin)->post(route('admin.films.store'), [
            'title' => 'Url Me', 'year' => 2024,
            'poster_file' => UploadedFile::fake()->image('p.jpg'),
        ]);
        $film = Film::where('title', 'Url Me')->sole();
        $uploaded = $film->poster_path;

        $this->actingAs($admin)->put(route('admin.films.update', $film), [
            'title' => 'Url Me', 'year' => 2024, 'poster_path' => 'https://example.com/poster.jpg',
        ]);

        $this->assertSame('https://example.com/poster.jpg', $film->fresh()->poster_path);
        Storage::disk('public')->assertMissing($uploaded);
    }

    public function test_non_video_files_are_rejected_as_trailers(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin())->post(route('admin.films.store'), [
            'title' => 'Bad Upload', 'year' => 2024,
            'trailer_file' => UploadedFile::fake()->create('notes.pdf', 10, 'application/pdf'),
            'poster_file' => UploadedFile::fake()->create('poster.txt', 1, 'text/plain'),
        ])->assertSessionHasErrors(['trailer_file', 'poster_file']);

        $this->assertSame(0, Film::count());
    }

    public function test_deleting_a_film_deletes_its_uploaded_files(): void
    {
        Storage::fake('public');
        $admin = $this->admin();
        $this->actingAs($admin)->post(route('admin.films.store'), [
            'title' => 'Delete Me', 'year' => 2024,
            'poster_file' => UploadedFile::fake()->image('p.jpg'),
            'trailer_file' => $this->video(),
        ]);
        $film = Film::where('title', 'Delete Me')->sole();
        $files = [$film->poster_path, $film->trailer_path];

        $this->actingAs($admin)->delete(route('admin.films.destroy', $film));

        Storage::disk('public')->assertMissing($files);
    }

    public function test_uploaded_videos_play_on_the_teasers_and_film_pages(): void
    {
        $film = Film::factory()->create([
            'title' => 'Video Film',
            'is_teaser' => true,
            'trailer_path' => 'films/trailers/full.mp4',
            'teaser_path' => 'films/teasers/short.mp4',
        ]);

        $this->get(route('teasers.index'))->assertSee('films/teasers/short.mp4', false);
        $this->get(route('films.show', $film))->assertSee('films/trailers/full.mp4', false);
    }
}
