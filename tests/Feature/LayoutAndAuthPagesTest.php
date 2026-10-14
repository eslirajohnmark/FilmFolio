<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\DiaryEntry;
use App\Models\Film;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LayoutAndAuthPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_login_is_not_linked_for_guests_but_has_a_keyboard_shortcut(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertDontSee('>Admin<', false)
            ->assertSee("e.ctrlKey && e.shiftKey && e.key.toLowerCase() === 'l'", false)
            ->assertSee(json_encode(route('admin.login')), false);
    }

    public function test_shortcut_sends_admins_straight_to_the_admin_panel(): void
    {
        $admin = Admin::factory()->create();

        $this->actingAsAdmin($admin)->get('/')->assertSee(json_encode(route('admin.dashboard')), false);
    }

    public function test_guest_home_page_matches_the_landing_design(): void
    {
        Film::factory()->create(['title' => 'Fresh Release']);

        $this->get('/')
            ->assertSee('Track films you’ve watched.', false)
            ->assertSee("Get Started - it's free!", false)
            ->assertSee('Just reviewed...')
            ->assertSee('Fresh Release');
    }

    public function test_signed_in_home_shows_what_friends_are_watching(): void
    {
        $user = User::factory()->create();
        $friend = User::factory()->create();
        $user->following()->attach($friend);
        DiaryEntry::create([
            'user_id' => $friend->id,
            'film_id' => Film::factory()->create(['title' => 'Friend Pick'])->id,
            'watched_on' => now(),
        ]);

        $this->actingAs($user)->get('/')
            ->assertSee('Welcome back')
            ->assertSee('Friend Pick');
    }

    public function test_auth_pages_render_the_new_design(): void
    {
        $this->get('/login')->assertOk()->assertSee('Welcome Back')->assertSee('name="login"', false);
        $this->get('/register')->assertOk()->assertSee('Create Account')->assertSee('name="name"', false);
        $this->get('/admin/login')->assertOk()->assertSee('Admin Sign In');
    }

    public function test_users_can_log_in_with_their_username(): void
    {
        $user = User::factory()->create(['username' => 'cinephile']);

        $this->post('/login', ['login' => 'cinephile', 'password' => 'password']);

        $this->assertAuthenticatedAs($user);
    }

    public function test_profile_tabs_show_watched_liked_and_favourite_films(): void
    {
        $user = User::factory()->create();
        [$watched, $liked, $favourite, $queued] = Film::factory(4)->sequence(
            ['title' => 'Seen It'], ['title' => 'Loved It'], ['title' => 'All-Time Fave'], ['title' => 'Someday'],
        )->create();
        $user->markWatched($watched);
        $user->markWatched($liked);
        $user->films()->updateExistingPivot($liked->id, ['liked' => true]);
        $user->favoriteFilms()->attach($favourite, ['position' => 1]);
        $user->watchlist()->attach($queued);

        $this->get(route('profile.show', $user))->assertSee('All-Time Fave');
        $this->get(route('profile.show', [$user, 'tab' => 'films']))->assertSee('Seen It')->assertSee('Loved It');
        $this->get(route('profile.show', [$user, 'tab' => 'likes']))->assertSee('Loved It')->assertDontSee('Seen It (');
        $this->get(route('profile.show', [$user, 'tab' => 'watchlist']))->assertSee('Someday');
    }
}
