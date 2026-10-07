<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuspendedUserLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_suspended_users_can_not_authenticate(): void
    {
        $user = User::factory()->create(['suspended_at' => now()]);

        $response = $this->post('/login', [
            'login' => $user->email,
            'password' => 'password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors(['login' => 'This account has been suspended.']);
    }
}
