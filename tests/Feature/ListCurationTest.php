<?php

namespace Tests\Feature;

use App\Livewire\ListLike;
use App\Models\Activity;
use App\Models\Film;
use App\Models\FilmList;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ListCurationTest extends TestCase
{
    use RefreshDatabase;

    private function list(string $name, array $flags = [], string $visibility = 'public'): FilmList
    {
        $list = FilmList::create(['user_id' => User::factory()->create()->id, 'name' => $name, 'visibility' => $visibility]);
        $list->forceFill($flags)->save();

        return $list;
    }

    public function test_lists_page_has_featured_popular_and_crew_pick_sections(): void
    {
        $this->list('Hand Picked', ['is_featured' => true]);
        $this->list('Official Horror', ['is_official' => true]);
        $loved = $this->list('Much Loved');
        $loved->likes()->attach(User::factory(3)->create());
        $this->list('Hidden Gem', ['is_featured' => true], 'private');

        $response = $this->get(route('lists.index'))->assertOk();

        $response->assertSeeInOrder(['Featured Lists', 'Hand Picked', 'Recently Popular', 'Much Loved', 'Crew Picks', 'Official Horror']);
        $response->assertDontSee('Hidden Gem');
    }

    public function test_members_can_like_and_unlike_public_lists(): void
    {
        $list = $this->list('Likeable');
        $member = User::factory()->create();

        Livewire::actingAs($member)->test(ListLike::class, ['list' => $list])
            ->call('toggle')
            ->assertSet('liked', true)
            ->assertSet('count', 1)
            ->call('toggle')
            ->assertSet('liked', false)
            ->assertSet('count', 0);
    }

    public function test_private_lists_can_not_be_liked_by_others(): void
    {
        $list = $this->list('Private', [], 'private');

        Livewire::actingAs(User::factory()->create())
            ->test(ListLike::class, ['list' => $list])
            ->call('toggle')
            ->assertForbidden();
    }

    public function test_list_page_shows_description_and_reviews_of_its_films(): void
    {
        $list = $this->list('Weekend Picks');
        $list->update(['description' => 'Easy weekend watching.']);
        $film = Film::factory()->create();
        $list->films()->attach($film, ['position' => 1]);
        Review::factory()->for(User::factory())->for($film)->create(['body' => 'Perfect for Sundays.']);

        $this->get(route('lists.show', $list))
            ->assertOk()
            ->assertSee('Easy weekend watching.')
            ->assertSee('Perfect for Sundays.');
    }

    public function test_members_page_has_most_active_featured_and_popular_sections(): void
    {
        $featured = User::factory()->create(['username' => 'featuredfay']);
        $featured->forceFill(['is_featured' => true])->save();
        $popular = User::factory()->create(['username' => 'popularpete']);
        $popular->followers()->attach(User::factory(3)->create());

        $this->get(route('members.index'))
            ->assertOk()
            ->assertSeeInOrder(['Most Active Reviewers', 'Featured Members', 'featuredfay', 'Popular Members', 'popularpete']);
    }

    public function test_members_can_delete_their_own_reviews_but_not_others(): void
    {
        $author = User::factory()->create();
        $review = Review::factory()->for($author)->for(Film::factory())->create();
        $other = Review::factory()->for(User::factory())->for(Film::factory())->create();

        $this->actingAs($author)->delete(route('reviews.destroy', $other))->assertForbidden();
        $this->actingAs($author)->delete(route('reviews.destroy', $review))->assertRedirect();

        $this->assertModelMissing($review);
        $this->assertModelExists($other);
    }

    public function test_admins_can_remove_inappropriate_reviews(): void
    {
        $review = Review::factory()->for(User::factory())->for(Film::factory())->create();

        $this->actingAsAdmin()
            ->delete(route('admin.reviews.destroy', $review))
            ->assertRedirect();

        $this->assertModelMissing($review);
    }

    public function test_members_can_not_use_the_admin_removal_route(): void
    {
        $review = Review::factory()->for(User::factory())->for(Film::factory())->create();

        $this->actingAs(User::factory()->create())
            ->delete(route('admin.reviews.destroy', $review))
            ->assertRedirect(route('admin.login'));

        $this->assertModelExists($review);
    }

    public function test_profile_has_ratings_and_activity_tabs(): void
    {
        $member = User::factory()->create();
        $film = Film::factory()->create(['title' => 'Rated Film']);
        Review::factory()->for($member)->for($film)->create(['rating' => 4, 'body' => null]);
        Activity::log($member, 'watched', $film);

        $this->get(route('profile.show', [$member, 'tab' => 'ratings']))->assertOk()->assertSee('Rated Film');
        $this->get(route('profile.show', [$member, 'tab' => 'activity']))->assertOk()->assertSee('watched');
    }

    public function test_client_navigation_matches_the_spec(): void
    {
        $member = User::factory()->create();

        $this->actingAs($member)->get(route('films.index'))
            ->assertSeeInOrder(['>Home<', '>Films<', '>Lists<', '>Members<', '>Teasers<', '>Profile<'], false);
    }
}
