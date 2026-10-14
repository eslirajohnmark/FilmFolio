<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\UserTable;
use App\Models\Admin;
use App\Models\Film;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AccountModerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_account_page_shows_member_activity_and_reviews(): void
    {
        $member = User::factory()->create(['username' => 'reviewer1']);
        Review::factory()->for($member)->for(Film::factory())->create(['body' => 'Member review text']);

        $this->actingAsAdmin()
            ->get(route('admin.accounts.show', $member))
            ->assertOk()
            ->assertSee('reviewer1')
            ->assertSee('Member review text');
    }

    public function test_admins_can_suspend_for_a_set_time_and_it_expires(): void
    {
        $member = User::factory()->create();

        $this->actingAsAdmin()
            ->post(route('admin.accounts.suspend', $member), ['days' => 7, 'reason' => 'Spam'])
            ->assertRedirect();

        $member->refresh();
        $this->assertTrue($member->isSuspended());
        $this->assertSame('Spam', $member->moderation_reason);
        $this->assertStringContainsString('suspended until', $member->accessBlockedMessage());

        $this->travel(8)->days();
        $this->assertFalse($member->fresh()->isSuspended());
    }

    public function test_suspended_and_banned_members_can_not_log_in(): void
    {
        $suspended = User::factory()->create(['suspended_at' => now(), 'suspended_until' => now()->addDays(3)]);
        $banned = User::factory()->create(['banned_at' => now()]);

        $this->post('/login', ['login' => $suspended->email, 'password' => 'password'])->assertSessionHasErrors('login');
        $this->assertGuest();

        $this->post('/login', ['login' => $banned->email, 'password' => 'password'])
            ->assertSessionHasErrors(['login' => 'This account has been banned.']);
        $this->assertGuest();
    }

    public function test_admins_can_ban_and_reinstate_members(): void
    {
        $admin = Admin::factory()->create();
        $member = User::factory()->create();

        $this->actingAsAdmin($admin)->post(route('admin.accounts.ban', $member), ['reason' => 'Abuse']);
        $this->assertTrue($member->fresh()->isBanned());

        $this->actingAsAdmin($admin)->post(route('admin.accounts.reinstate', $member));
        $member->refresh();
        $this->assertFalse($member->isBanned());
        $this->assertFalse($member->isSuspended());
    }

    public function test_admins_are_not_listed_or_managed_as_member_accounts(): void
    {
        $admin = Admin::factory()->create(['username' => 'boss_admin']);
        User::factory()->create(['username' => 'regular_member']);

        $this->actingAsAdmin($admin);

        Livewire::test(UserTable::class)
            ->assertSee('regular_member')
            ->assertDontSee('boss_admin');

        $this->assertDatabaseMissing('users', ['email' => $admin->email]);
    }

    public function test_admins_can_feature_and_delete_members(): void
    {
        $admin = Admin::factory()->create();
        $member = User::factory()->create();

        $this->actingAsAdmin($admin)->patch(route('admin.accounts.feature', $member));
        $this->assertTrue($member->fresh()->is_featured);

        $this->actingAsAdmin($admin)->delete(route('admin.accounts.destroy', $member))->assertRedirect(route('admin.accounts.index'));
        $this->assertModelMissing($member);
    }

    public function test_members_can_not_use_admin_pages(): void
    {
        $member = User::factory()->create();
        $target = User::factory()->create();

        $this->actingAs($member)->get(route('admin.dashboard'))->assertRedirect(route('admin.login'));
        $this->actingAs($member)->get(route('admin.accounts.index'))->assertRedirect(route('admin.login'));
        $this->actingAs($member)->post(route('admin.accounts.ban', $target))->assertRedirect(route('admin.login'));

        $this->assertFalse($target->fresh()->isBanned());
    }
}
