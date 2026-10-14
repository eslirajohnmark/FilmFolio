<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\Film;
use App\Models\FilmList;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminListsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admins_can_create_an_official_genre_list_auto_filled_with_top_rated_films(): void
    {
        $admin = Admin::factory()->create();
        $best = Film::factory()->create(['genre' => 'Horror', 'title' => 'Best Horror']);
        $worse = Film::factory()->create(['genre' => 'Horror', 'title' => 'Worse Horror']);
        Film::factory()->create(['genre' => 'Comedy']);
        Review::factory()->for(User::factory())->for($best)->create(['rating' => 5]);
        Review::factory()->for(User::factory())->for($worse)->create(['rating' => 2]);

        $this->actingAsAdmin($admin)->post(route('admin.lists.store'), [
            'name' => 'Essential Horror',
            'description' => 'Scary good.',
            'genre' => 'Horror',
            'auto_fill' => 10,
        ])->assertRedirect();

        $list = FilmList::where('name', 'Essential Horror')->sole();
        $this->assertTrue($list->is_official);
        $this->assertSame('public', $list->visibility);
        $this->assertSame(['Best Horror', 'Worse Horror'], $list->films()->orderBy('list_items.position')->pluck('title')->all());
    }

    public function test_admins_can_feature_and_delete_lists(): void
    {
        $admin = Admin::factory()->create();
        $list = FilmList::create(['user_id' => User::factory()->create()->id, 'name' => 'Member List', 'visibility' => 'public']);

        $this->actingAsAdmin($admin)->patch(route('admin.lists.feature', $list));
        $this->assertTrue($list->fresh()->is_featured);

        $this->actingAsAdmin($admin)->delete(route('admin.lists.destroy', $list));
        $this->assertModelMissing($list);
    }

    public function test_private_lists_can_not_be_featured(): void
    {
        $admin = Admin::factory()->create();
        $list = FilmList::create(['user_id' => User::factory()->create()->id, 'name' => 'Secret', 'visibility' => 'private']);

        $this->actingAsAdmin($admin)->patch(route('admin.lists.feature', $list));

        $this->assertFalse($list->fresh()->is_featured);
    }

    public function test_admin_list_pages_render(): void
    {
        $admin = Admin::factory()->create();

        $this->actingAsAdmin($admin)->get(route('admin.lists.index'))->assertOk();
        $this->actingAsAdmin($admin)->get(route('admin.lists.create'))->assertOk();
    }
}
