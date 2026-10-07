<?php

namespace Tests\Feature\Admin;

use App\Models\Film;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountModerationTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_account_page_shows_member_activity_and_reviews(): void
    {
        $member = User::factory()->create(['username' => 'reviewer1']);
        Review::factory()->for($member)->for(Film::factory())->create(['body' => 'Member review text']);

        $this->actingAs($this->admin())
            ->get(route('admin.accounts.show', $member))
            ->assertOk()
            ->assertSee('reviewer1')
            ->assertSee('Member review text');
    }

    public function test_admins_can_suspend_for_a_set_time_and_it_expires(): void
    {
        $member = User::factory()->create();

        $this->actingAs($this->admin())
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
        $admin = $this->admin();
        $member = User::factory()->create();

        $this->actingAs($admin)->post(route('admin.accounts.ban', $member), ['reason' => 'Abuse']);
        $this->assertTrue($member->fresh()->isBanned());

        $this->actingAs($admin)->post(route('admin.accounts.reinstate', $member));
        $member->refresh();
        $this->assertFalse($member->isBanned());
        $this->assertFalse($member->isSuspended());
    }

    public function test_admin_accounts_can_not_be_suspended_banned_or_deleted(): void
    {
        $admin = $this->admin();
        $otherAdmin = $this->admin();

        $this->actingAs($admin)->post(route('admin.accounts.suspend', $admin), ['days' => 1]);
        $this->actingAs($admin)->post(route('admin.accounts.ban', $otherAdmin));
        $this->actingAs($admin)->delete(route('admin.accounts.destroy', $otherAdmin));

        $this->assertFalse($admin->fresh()->isSuspended());
        $this->assertFalse($otherAdmin->fresh()->isBanned());
        $this->assertModelExists($otherAdmin);
    }

    public function test_admins_can_feature_and_delete_members(): void
    {
        $admin = $this->admin();
        $member = User::factory()->create();

        $this->actingAs($admin)->patch(route('admin.accounts.feature', $member));
        $this->assertTrue($member->fresh()->is_featured);

        $this->actingAs($admin)->delete(route('admin.accounts.destroy', $member))->assertRedirect(route('admin.accounts.index'));
        $this->assertModelMissing($member);
    }

    public function test_members_can_not_use_admin_pages(): void
    {
        $member = User::factory()->create();

        $this->actingAs($member)->get(route('admin.dashboard'))->assertForbidden();
        $this->actingAs($member)->get(route('admin.accounts.index'))->assertForbidden();
        $this->actingAs($member)->post(route('admin.accounts.ban', User::factory()->create()))->assertForbidden();
    }
}
