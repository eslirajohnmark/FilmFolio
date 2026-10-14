<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'username' => 'test_user',
            'email' => 'test@example.com',
            'password' => 'John123!',
            'password_confirmation' => 'John123!',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('home', absolute: false))
            ->assertSessionHas('status', 'Account created successfully. Welcome to FilmFolio!');
    }

    public function test_weak_passwords_are_rejected(): void
    {
        foreach (['john1245', 'JOHN1245!', 'john1245!', 'Johnjohn!', 'John1245', 'Jo12!'] as $password) {
            $this->post('/register', [
                'name' => 'Test User',
                'username' => 'test_user',
                'email' => 'test@example.com',
                'password' => $password,
                'password_confirmation' => $password,
            ])->assertSessionHasErrors('password');
        }

        $this->assertGuest();
    }

    public function test_registration_form_lists_the_password_rules(): void
    {
        $this->get('/register')
            ->assertSee('data-require-filled', false)
            ->assertSee('data-password-rules="password"', false)
            ->assertSeeInOrder(['At least 8 characters', 'One uppercase letter', 'One lowercase letter', 'One number', 'One special character']);
    }
}
