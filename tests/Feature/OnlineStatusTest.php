<?php

namespace Tests\Feature;

use App\Livewire\Admin\UserPresence;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class OnlineStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_loading_a_page_records_when_the_member_was_last_seen(): void
    {
        $member = User::factory()->create();
        $this->travelTo('2026-10-20 10:00:00');

        $this->actingAs($member)->get(route('live'));

        $this->assertSame('2026-10-20 10:00:00', $member->fresh()->last_seen_at->toDateTimeString());
    }

    public function test_last_seen_is_written_at_most_once_a_minute(): void
    {
        $member = User::factory()->create();
        $this->travelTo('2026-10-20 10:00:00');
        $this->actingAs($member)->get(route('live'));

        $this->travelTo('2026-10-20 10:00:30');
        $this->actingAs($member->fresh())->get(route('live'));
        $afterHalfAMinute = $member->fresh()->last_seen_at->toDateTimeString();
        $this->travelTo('2026-10-20 10:01:01');
        $this->actingAs($member->fresh())->get(route('live'));

        $this->assertSame('2026-10-20 10:00:00', $afterHalfAMinute);
        $this->assertSame('2026-10-20 10:01:01', $member->fresh()->last_seen_at->toDateTimeString());
    }

    public function test_accounts_page_shows_who_is_online_and_how_long_ago_the_others_were_seen(): void
    {
        $this->travelTo('2026-10-20 10:00:00');
        User::factory()->create(['username' => 'onlineolive'])->forceFill(['last_seen_at' => now()->subSeconds(30)])->save();
        User::factory()->create(['username' => 'awayalex'])->forceFill(['last_seen_at' => now()->subMinutes(5)])->save();
        User::factory()->create(['username' => 'newnina']);

        $response = $this->actingAsAdmin()->get(route('admin.accounts.index'));

        $response->assertSeeInOrder(['onlineolive', 'class="presence is-online"'], false)
            ->assertSeeInOrder(['awayalex', 'Offline ·', '5 minutes ago'], false)
            ->assertSeeInOrder(['newnina', 'Not seen yet'], false);
    }

    public function test_a_member_counts_as_offline_two_minutes_after_they_were_last_seen(): void
    {
        $this->travelTo('2026-10-20 10:00:00');
        $member = User::factory()->create();
        $member->forceFill(['last_seen_at' => now()])->save();

        Livewire::test(UserPresence::class, ['member' => $member, 'variant' => 'avatar'])
            ->assertSeeHtml('presence-avatar is-online');

        $this->travelTo('2026-10-20 10:02:01');

        Livewire::test(UserPresence::class, ['member' => $member->fresh()])
            ->assertSeeHtml('Offline ·')
            ->assertSee('2 minutes ago');
    }
}
