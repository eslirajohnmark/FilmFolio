<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_admins_can_authenticate(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->post('/admin/login', [
            'login' => $admin->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($admin);
        $response->assertRedirect(route('admin.dashboard'));
    }

    public function test_non_admins_are_rejected(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/admin/login', [
            'login' => $user->email,
            'password' => 'password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('login');
    }

    public function test_admin_login_is_rate_limited(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        foreach (range(1, 5) as $attempt) {
            $this->post('/admin/login', ['login' => $admin->email, 'password' => 'wrong-password']);
        }

        $response = $this->post('/admin/login', [
            'login' => $admin->email,
            'password' => 'password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('login');
    }
}
