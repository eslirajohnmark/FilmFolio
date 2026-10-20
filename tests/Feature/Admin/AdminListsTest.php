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

    public function test_admins_can_feature_and_delete_crew_picks(): void
    {
        $admin = Admin::factory()->create();
        $crewPick = new FilmList(['name' => 'Essential Noir', 'visibility' => 'public']);
        $crewPick->forceFill(['admin_id' => $admin->id, 'is_official' => true])->save();

        $this->actingAsAdmin($admin)->patch(route('admin.lists.feature', $crewPick));
        $this->assertTrue($crewPick->fresh()->is_featured);

        $this->actingAsAdmin($admin)->delete(route('admin.lists.destroy', $crewPick));
        $this->assertModelMissing($crewPick);
    }

    public function test_admins_can_feature_a_members_list_but_not_delete_it(): void
    {
        $list = FilmList::create(['user_id' => User::factory()->create()->id, 'name' => 'Member List', 'visibility' => 'public']);

        $this->actingAsAdmin()->patch(route('admin.lists.feature', $list));
        $this->actingAsAdmin()->delete(route('admin.lists.destroy', $list))->assertForbidden();

        $this->assertModelExists($list);
        $this->assertTrue($list->fresh()->is_featured);
    }

    public function test_members_lists_have_no_delete_button_in_the_admin_list_table(): void
    {
        $list = FilmList::create(['user_id' => User::factory()->create()->id, 'name' => 'Member List', 'visibility' => 'public']);

        $response = $this->actingAsAdmin()->get(route('admin.lists.index'));

        $response->assertSee('action="'.route('admin.lists.feature', $list).'"', false)
            ->assertDontSee('action="'.route('admin.lists.destroy', $list).'"', false);
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
