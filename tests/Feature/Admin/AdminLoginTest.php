<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_admins_can_authenticate(): void
    {
        $admin = Admin::factory()->create();

        $response = $this->post('/admin/login', [
            'login' => $admin->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($admin, 'admin');
        $this->assertGuest('web');
        $response->assertRedirect(route('admin.dashboard'));
    }

    public function test_member_accounts_can_not_sign_in_to_the_admin_panel(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/admin/login', [
            'login' => $user->email,
            'password' => 'password',
        ]);

        $this->assertGuest('admin');
        $this->assertGuest('web');
        $response->assertSessionHasErrors('login');
    }

    public function test_admin_login_is_rate_limited(): void
    {
        $admin = Admin::factory()->create();

        foreach (range(1, 5) as $attempt) {
            $this->post('/admin/login', ['login' => $admin->email, 'password' => 'wrong-password']);
        }

        $response = $this->post('/admin/login', [
            'login' => $admin->email,
            'password' => 'password',
        ]);

        $this->assertGuest('admin');
        $response->assertSessionHasErrors('login');
    }

    public function test_admins_can_sign_out_without_signing_out_the_member_session(): void
    {
        $member = User::factory()->create();

        $this->actingAs($member)->actingAsAdmin()
            ->post(route('admin.logout'))
            ->assertRedirect(route('admin.login'));

        $this->assertGuest('admin');
        $this->assertAuthenticatedAs($member, 'web');
    }
}
