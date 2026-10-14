<?php

namespace Tests\Feature;

use App\Livewire\ActivityFeed;
use App\Models\Activity;
use App\Models\Admin;
use App\Models\Film;
use App\Models\FilmList;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FeedVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_feeds_skip_activities_whose_subject_was_deleted(): void
    {
        $user = User::factory()->create();
        $followed = User::factory()->create();
        $film = Film::factory()->create(['title' => 'Kept Film']);
        Activity::log($user, 'followed', $followed);
        Activity::log($user, 'watched', $film);

        $followed->delete();

        $this->actingAs($user)->get('/')->assertOk()->assertSee('Kept Film');
        Livewire::actingAs($user)->test(ActivityFeed::class)
            ->assertSee('Kept Film')
            ->assertDontSee('started following');
    }

    public function test_private_list_activity_is_hidden_from_feeds(): void
    {
        $user = User::factory()->create();
        $public = FilmList::create(['user_id' => $user->id, 'name' => 'Public Picks', 'visibility' => 'public']);
        $private = FilmList::create(['user_id' => $user->id, 'name' => 'Secret Stash', 'visibility' => 'private']);
        Activity::log($user, 'listed', $public);
        Activity::log($user, 'listed', $private);

        Livewire::actingAs(User::factory()->create())->test(ActivityFeed::class)
            ->set('scope', 'global')
            ->assertSee('Public Picks')
            ->assertDontSee('Secret Stash');
    }

    public function test_admin_sign_ins_are_not_recorded_in_feeds(): void
    {
        $admin = Admin::factory()->create(['username' => 'the_admin']);

        $this->post('/admin/login', ['login' => $admin->email, 'password' => 'password']);

        $this->assertSame(0, Activity::count());
        Livewire::actingAs(User::factory()->create())->test(ActivityFeed::class)
            ->set('scope', 'global')
            ->assertSee('Nothing here yet');
    }

    public function test_home_feed_uses_readable_activity_text(): void
    {
        $user = User::factory()->create();
        Activity::log($user, 'watchlist_added', Film::factory()->create(['title' => 'Arrival']));

        $this->actingAs($user)->get('/')
            ->assertSee('to their watchlist')
            ->assertDontSee('watchlist_added');
    }
}
