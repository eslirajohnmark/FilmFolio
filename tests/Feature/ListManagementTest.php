<?php

namespace Tests\Feature;

use App\Livewire\AddToList;
use App\Models\Film;
use App\Models\FilmList;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ListManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_private_lists_can_only_be_viewed_by_their_owner(): void
    {
        $owner = User::factory()->create();
        $list = FilmList::create(['user_id' => $owner->id, 'name' => 'Secret Stash', 'visibility' => 'private']);

        $this->get(route('lists.show', $list))->assertForbidden();
        $this->actingAs(User::factory()->create())->get(route('lists.show', $list))->assertForbidden();
        $this->actingAs($owner)->get(route('lists.show', $list))->assertOk()->assertSee('Secret Stash');
    }

    public function test_owners_can_rename_and_delete_lists(): void
    {
        $owner = User::factory()->create();
        $list = FilmList::create(['user_id' => $owner->id, 'name' => 'Old Name', 'visibility' => 'public']);

        $this->actingAs($owner)
            ->patch(route('lists.update', $list), ['name' => 'New Name', 'visibility' => 'private'])
            ->assertRedirect(route('lists.show', $list));
        $this->assertSame('New Name', $list->fresh()->name);
        $this->assertSame('private', $list->fresh()->visibility);

        $this->actingAs($owner)->delete(route('lists.destroy', $list))->assertRedirect(route('lists.index'));
        $this->assertModelMissing($list);
    }

    public function test_other_users_can_not_change_a_list(): void
    {
        $list = FilmList::create(['user_id' => User::factory()->create()->id, 'name' => 'Mine', 'visibility' => 'public']);
        $intruder = User::factory()->create();

        $this->actingAs($intruder)
            ->patch(route('lists.update', $list), ['name' => 'Hacked', 'visibility' => 'public'])
            ->assertForbidden();
        $this->actingAs($intruder)->delete(route('lists.destroy', $list))->assertForbidden();

        $this->assertSame('Mine', $list->fresh()->name);
    }

    public function test_films_can_be_added_to_and_removed_from_lists(): void
    {
        $owner = User::factory()->create();
        $film = Film::factory()->create();
        $list = FilmList::create(['user_id' => $owner->id, 'name' => 'Favourites', 'visibility' => 'public']);

        Livewire::actingAs($owner)->test(AddToList::class, ['film' => $film])->call('toggle', $list->id);
        $this->assertTrue($list->films()->whereKey($film->id)->exists());
        $this->get(route('lists.index'))->assertSee('1 Film');

        $this->actingAs($owner)->delete(route('lists.films.destroy', [$list, $film]));
        $this->assertFalse($list->films()->whereKey($film->id)->exists());
    }

    public function test_users_can_not_add_films_to_someone_elses_list(): void
    {
        $list = FilmList::create(['user_id' => User::factory()->create()->id, 'name' => 'Theirs', 'visibility' => 'public']);
        $film = Film::factory()->create();

        Livewire::actingAs(User::factory()->create())
            ->test(AddToList::class, ['film' => $film])
            ->call('toggle', $list->id)
            ->assertForbidden();

        $this->assertSame(0, $list->films()->count());
    }
}
