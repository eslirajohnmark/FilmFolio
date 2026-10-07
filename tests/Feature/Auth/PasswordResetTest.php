<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use RuntimeException;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_password_link_screen_can_be_rendered(): void
    {
        $response = $this->get('/forgot-password');

        $response->assertStatus(200);
    }

    public function test_reset_password_link_can_be_requested(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_reset_password_screen_can_be_rendered(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) {
            $response = $this->get('/reset-password/'.$notification->token);

            $response->assertStatus(200);

            return true;
        });
    }

    public function test_password_can_be_reset_with_valid_token(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
            $response = $this->post('/reset-password', [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'password',
                'password_confirmation' => 'password',
            ]);

            $response
                ->assertSessionHasNoErrors()
                ->assertRedirect(route('login'));

            return true;
        });
    }

    public function test_reset_link_can_be_requested_with_a_username(): void
    {
        Notification::fake();

        $user = User::factory()->create(['username' => 'cinephile']);

        $this->post('/forgot-password', ['email' => 'cinephile'])->assertSessionHasNoErrors();

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_unknown_accounts_get_the_same_response_as_real_ones(): void
    {
        Notification::fake();

        $this->from('/forgot-password')
            ->post('/forgot-password', ['email' => 'nobody@example.com'])
            ->assertRedirect('/forgot-password')
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status');

        Notification::assertNothingSent();
    }

    public function test_local_requests_for_a_real_account_are_sent_to_the_mailpit_inbox(): void
    {
        Notification::fake();
        $this->runInEnvironmentWithMailpit('local');

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email])
            ->assertRedirect('http://localhost:8025');

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_local_requests_for_an_unknown_account_stay_on_the_form(): void
    {
        $this->runInEnvironmentWithMailpit('local');

        $this->from('/forgot-password')
            ->post('/forgot-password', ['email' => 'nobody@example.com'])
            ->assertRedirect('/forgot-password')
            ->assertSessionHas('status');
    }

    public function test_the_mailpit_redirect_never_happens_outside_local(): void
    {
        Notification::fake();
        $this->runInEnvironmentWithMailpit('production');

        $user = User::factory()->create();

        $this->from('/forgot-password')
            ->post('/forgot-password', ['email' => $user->email])
            ->assertRedirect('/forgot-password')
            ->assertSessionHas('status');
    }

    public function test_a_mail_failure_shows_an_error_instead_of_crashing(): void
    {
        Password::shouldReceive('sendResetLink')->andThrow(new RuntimeException('SMTP is down'));

        $this->from('/forgot-password')
            ->post('/forgot-password', ['email' => 'someone@example.com'])
            ->assertRedirect('/forgot-password')
            ->assertSessionHasErrors('email');
    }

    public function test_reset_email_is_branded_and_links_to_the_reset_page(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user) {
            $mail = $notification->toMail($user);

            $this->assertSame('Reset your FilmFolio password', $mail->subject);
            $this->assertSame(route('password.reset', ['token' => $notification->token, 'email' => $user->email]), $mail->actionUrl);

            return true;
        });
    }

    /**
     * Run the request in the given environment with a Mailpit inbox configured.
     *
     * Leaving the "testing" environment re-enables CSRF checks, so they are
     * turned off here to keep the request focused on the redirect.
     */
    private function runInEnvironmentWithMailpit(string $environment): void
    {
        $this->app['env'] = $environment;
        config(['mail.mailpit_url' => 'http://localhost:8025']);
        $this->withoutMiddleware(PreventRequestForgery::class);
    }
}
