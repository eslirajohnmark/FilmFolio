<?php

namespace Tests\Feature;

use App\Models\Film;
use App\Models\InterestSignal;
use App\Models\User;
use App\Services\LiveUpdates;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LiveUpdatesTest extends TestCase
{
    use RefreshDatabase;

    public function test_adding_a_film_changes_the_version_open_pages_check(): void
    {
        $before = $this->getJson(route('live'))->json('version');

        Film::factory()->create();

        $this->assertNotSame($before, $this->getJson(route('live'))->json('version'));
    }

    public function test_featuring_a_member_changes_the_version_but_a_member_editing_their_bio_does_not(): void
    {
        $member = User::factory()->create();
        $live = app(LiveUpdates::class);
        $live->changed();
        $start = $live->version();
        $this->travel(5)->seconds();

        $member->update(['bio' => 'I love noir.']);
        $afterBio = $live->version();
        $member->forceFill(['is_featured' => true])->save();

        $this->assertSame($start, $afterBio);
        $this->assertNotSame($afterBio, $live->version());
    }

    public function test_a_page_refreshing_itself_does_not_count_as_viewing_the_film_again(): void
    {
        $member = User::factory()->create();
        $film = Film::factory()->create(['genre' => 'Horror']);

        $this->actingAs($member)->get(route('films.show', $film), [LiveUpdates::REFRESH_HEADER => '1']);
        $signalsAfterRefresh = InterestSignal::count();
        $this->actingAs($member)->get(route('films.show', $film));

        $this->assertSame(0, $signalsAfterRefresh);
        $this->assertSame(1, InterestSignal::count());
    }
}
