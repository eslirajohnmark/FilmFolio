<?php

namespace Tests\Feature\Admin;

use App\Models\DiaryEntry;
use App\Models\Film;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_shows_member_counts_popular_genres_and_decades(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $members = User::factory(3)->create();
        $scifi = Film::factory()->create(['genre' => 'Sci-Fi', 'year' => 1982]);
        $drama = Film::factory()->create(['genre' => 'Drama', 'year' => 2015]);

        foreach ($members as $member) {
            DiaryEntry::create(['user_id' => $member->id, 'film_id' => $scifi->id, 'watched_on' => now()]);
        }
        Review::factory()->for($members->first())->for($drama)->create();

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk()
            ->assertSee('<b>4</b><span>Members</span>', false)
            ->assertSee('Popular genres')
            ->assertSee('Sci-Fi')
            ->assertSee('Drama')
            ->assertSee('1980s')
            ->assertSee('2010s')
            ->assertSee('This week')
            ->assertSee('This month')
            ->assertSee('This year');

        $genres = $response->viewData('genres');
        $this->assertSame(['Sci-Fi' => 3, 'Drama' => 1], $genres->all());
    }

    public function test_period_statistics_count_only_recent_records(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->travel(-60)->days();
        User::factory()->create();
        $this->travelBack();

        $periods = $this->actingAs($admin)->get(route('admin.dashboard'))->viewData('periods');

        $this->assertSame(1, $periods['New members']['This week']);
        $this->assertSame(1, $periods['New members']['This month']);
        $this->assertSame(2, $periods['New members']['This year']);
    }

    public function test_admin_login_and_shortcut_land_on_the_dashboard(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->post('/admin/login', ['login' => $admin->email, 'password' => 'password'])
            ->assertRedirect(route('admin.dashboard'));
    }
}
