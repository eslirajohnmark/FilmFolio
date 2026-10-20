<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pressing Back after signing in (or out) must ask the server again rather than
 * show a stored page, and must never show what was typed into the sign-in form.
 */
class BackButtonTest extends TestCase
{
    use RefreshDatabase;

    public function test_pages_tell_the_browser_not_to_keep_a_copy(): void
    {
        foreach (['/login', '/register', '/admin/login', '/'] as $page) {
            $cacheControl = $this->get($page)->headers->get('Cache-Control');

            $this->assertStringContainsString('no-store', $cacheControl, $page);
        }

        $this->actingAs(User::factory()->create())->get(route('profile.edit'))
            ->assertHeader('Pragma', 'no-cache');
    }

    public function test_signed_in_members_going_back_to_guest_pages_land_on_home(): void
    {
        $member = User::factory()->create();

        foreach (['/login', '/register', '/forgot-password'] as $page) {
            $this->actingAs($member)->get($page)->assertRedirect(route('home'));
        }
    }

    public function test_signed_in_admins_going_back_to_the_admin_login_land_on_the_dashboard(): void
    {
        $this->actingAsAdmin()->get(route('admin.login'))->assertRedirect(route('admin.dashboard'));
    }

    public function test_after_signing_out_account_pages_need_signing_in_again(): void
    {
        $member = User::factory()->create();

        $this->actingAs($member)->post(route('logout'));

        $this->get(route('profile.edit'))->assertRedirect(route('login'));
        $this->get(route('admin.dashboard'))->assertRedirect(route('admin.login'));
    }

    public function test_sign_in_forms_are_not_refilled_by_the_browser(): void
    {
        foreach (['/login', '/register', '/forgot-password', '/admin/login'] as $page) {
            $this->get($page)
                ->assertSee('data-clear-on-back', false)
                ->assertSee('autocomplete="off"', false);
        }
    }
}
