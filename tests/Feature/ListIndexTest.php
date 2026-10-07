<?php

namespace Tests\Feature;

use App\Models\FilmList;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_private_lists_are_only_visible_to_their_owner(): void
    {
        $owner = User::factory()->create();
        $visitor = User::factory()->create();
        FilmList::create(['user_id' => $owner->id, 'name' => 'Public Picks', 'visibility' => 'public']);
        FilmList::create(['user_id' => $owner->id, 'name' => 'Secret Stash', 'visibility' => 'private']);

        $this->get('/lists')->assertSee('Public Picks')->assertDontSee('Secret Stash');
        $this->actingAs($visitor)->get('/lists')->assertSee('Public Picks')->assertDontSee('Secret Stash');
        $this->actingAs($owner)->get('/lists')->assertSee('Public Picks')->assertSee('Secret Stash');
    }

    public function test_users_can_create_lists(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/lists', ['name' => 'Weekend Watch', 'visibility' => 'public'])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('film_lists', ['user_id' => $user->id, 'name' => 'Weekend Watch']);
    }
}
