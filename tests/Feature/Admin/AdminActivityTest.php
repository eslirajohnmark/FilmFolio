<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\UserTable;
use App\Models\Film;
use App\Models\Review;
use App\Models\ReviewComment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminActivityTest extends TestCase
{
    use RefreshDatabase;

    private function review(string $body, string $createdAt): Review
    {
        $review = Review::factory()->create([
            'user_id' => User::factory()->create()->id,
            'film_id' => Film::factory()->create()->id,
            'body' => $body,
        ]);
        $review->forceFill(['created_at' => $createdAt])->save();

        return $review;
    }

    public function test_activity_shows_the_reviews_from_the_chosen_period_only(): void
    {
        $this->travelTo('2026-10-20 12:00:00');
        $this->review('Fresh take on it', '2026-10-19 09:00:00');
        $this->review('Old news by now', '2026-10-05 09:00:00');

        $thisWeek = $this->actingAsAdmin()->get(route('admin.activity.index', ['type' => 'reviews', 'period' => 'week']));
        $thisMonth = $this->actingAsAdmin()->get(route('admin.activity.index', ['type' => 'reviews', 'period' => 'month']));

        $thisWeek->assertSee('Fresh take on it')->assertDontSee('Old news by now');
        $thisMonth->assertSee('Fresh take on it')->assertSee('Old news by now');
    }

    public function test_a_week_picked_on_the_dashboard_chart_shows_only_that_week(): void
    {
        $this->travelTo('2026-10-20 12:00:00');
        $this->review('Inside the week', '2026-10-07 09:00:00');
        $this->review('The week after', '2026-10-13 09:00:00');

        $response = $this->actingAsAdmin()->get(route('admin.activity.index', ['type' => 'reviews', 'week' => '2026-10-05']));

        $response->assertSee('Inside the week')->assertDontSee('The week after');
    }

    public function test_the_comments_tab_lists_comments(): void
    {
        $review = $this->review('Worth a watch', now()->toDateTimeString());
        ReviewComment::factory()->for($review)->for(User::factory())->create(['body' => 'Totally agree with this']);

        $response = $this->actingAsAdmin()->get(route('admin.activity.index', ['type' => 'comments']));

        $response->assertSee('Totally agree with this');
    }

    public function test_members_can_not_open_the_activity_page(): void
    {
        $response = $this->actingAs(User::factory()->create())->get(route('admin.activity.index'));

        $response->assertRedirect(route('admin.login'));
    }

    public function test_dashboard_numbers_link_to_the_pages_that_list_them(): void
    {
        Film::factory()->create(['genre' => 'Drama', 'year' => 2015]);
        Review::factory()->create(['user_id' => User::factory()->create()->id, 'film_id' => Film::first()->id]);

        $response = $this->actingAsAdmin()->get(route('admin.dashboard'));

        $response->assertSee(route('admin.accounts.index', ['joined' => 'week']), false)
            ->assertSee(route('admin.films.index', ['filter' => 'teasers']), false)
            ->assertSee(route('admin.accounts.index', ['status' => 'banned']), false)
            ->assertSee(e(route('admin.activity.index', ['type' => 'comments', 'period' => 'year'])), false)
            ->assertSee(route('admin.films.index', ['genre' => 'Drama']), false)
            ->assertSee(route('admin.films.index', ['decade' => 2010]), false);
    }

    public function test_films_can_be_filtered_by_genre_and_decade(): void
    {
        Film::factory()->create(['title' => 'Neon Drama', 'genre' => 'Drama', 'year' => 2015]);
        Film::factory()->create(['title' => 'Old Drama', 'genre' => 'Drama', 'year' => 1985]);
        Film::factory()->create(['title' => 'Space Comedy', 'genre' => 'Comedy', 'year' => 2016]);

        $response = $this->actingAsAdmin()->get(route('admin.films.index', ['genre' => 'Drama', 'decade' => 2010]));

        $response->assertSee('Neon Drama')->assertDontSee('Old Drama')->assertDontSee('Space Comedy');
    }

    public function test_accounts_can_be_filtered_to_members_who_joined_this_week(): void
    {
        $this->travelTo('2026-10-20 12:00:00');
        User::factory()->create(['username' => 'newcomer']);
        User::factory()->create(['username' => 'oldtimer'])->forceFill(['created_at' => '2026-08-01 10:00:00'])->save();
        $this->actingAsAdmin();

        Livewire::withQueryParams(['joined' => 'week'])->test(UserTable::class)
            ->assertSee('newcomer')
            ->assertDontSee('oldtimer');
    }
}
