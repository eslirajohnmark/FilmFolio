<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Green/red banners, the password rules and their checklist, and the welcome message.
 */
class AuthFeedbackTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, string>
     */
    private function registration(string $password): array
    {
        return [
            'name' => 'John Doe',
            'username' => 'johndoe',
            'email' => 'john@example.com',
            'password' => $password,
            'password_confirmation' => $password,
        ];
    }

    public function test_passwords_need_upper_and_lower_case_a_number_and_a_special_character(): void
    {
        foreach (['john1245', 'John1245', 'john123!', 'JOHN123!', 'John!!!!', 'Jo1!'] as $weak) {
            $this->post('/register', $this->registration($weak))->assertSessionHasErrors('password');
            $this->assertGuest();
        }

        $this->post('/register', $this->registration('John123!'))->assertSessionHasNoErrors();
        $this->assertAuthenticated();
    }

    public function test_creating_an_account_shows_a_green_banner(): void
    {
        $this->followingRedirects()
            ->post('/register', $this->registration('John123!'))
            ->assertSee('banner-success', false)
            ->assertSee('Account created successfully');
    }

    public function test_a_failed_registration_shows_a_red_banner_and_marks_the_missing_fields(): void
    {
        $this->followingRedirects()
            ->from('/register')
            ->post('/register', ['name' => '', 'username' => '', 'email' => '', 'password' => ''])
            ->assertSee('banner-error', false)
            ->assertSee('Registration failed')
            ->assertSee('has-error', false);
    }

    public function test_logging_in_shows_login_successful_and_a_wrong_password_shows_invalid_credentials(): void
    {
        $member = User::factory()->create();

        $this->followingRedirects()
            ->from('/login')
            ->post('/login', ['login' => $member->email, 'password' => 'wrong-password'])
            ->assertSee('banner-error', false)
            ->assertSee('Invalid Credentials');

        $this->followingRedirects()
            ->post('/login', ['login' => $member->email, 'password' => 'password'])
            ->assertSee('banner-success', false)
            ->assertSee('Login Successful')
            ->assertSee('Welcome back');
    }

    public function test_sign_up_forms_have_the_password_checklist_and_wait_for_input(): void
    {
        $this->get('/register')
            ->assertSee('data-require-filled', false)
            ->assertSee('At least 8 characters')
            ->assertSee('One special character', false)
            ->assertSee('data-matches="password"', false);

        $this->get('/login')->assertSee('data-require-filled', false);
    }
}
