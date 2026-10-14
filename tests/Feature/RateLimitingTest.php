<?php

namespace Tests\Feature;

use App\Models\Film;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RateLimitingTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_is_limited_per_ip_address(): void
    {
        foreach (range(1, 10) as $i) {
            $this->post('/register', [
                'name' => "Member {$i}",
                'username' => "member{$i}",
                'email' => "member{$i}@example.com",
                'password' => 'password',
                'password_confirmation' => 'password',
            ]);
            auth()->logout();
        }

        $this->post('/register', [
            'name' => 'One Too Many',
            'username' => 'onetoomany',
            'email' => 'onetoomany@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertTooManyRequests();

        $this->assertDatabaseMissing('users', ['username' => 'onetoomany']);
    }

    public function test_sign_in_attempts_are_limited_per_ip_address_across_usernames(): void
    {
        foreach (range(1, 10) as $i) {
            $this->post('/login', ['login' => "guess{$i}", 'password' => 'wrong'])->assertSessionHasErrors('login');
        }

        $this->post('/login', ['login' => 'guess11', 'password' => 'wrong'])->assertTooManyRequests();
    }

    public function test_posting_reviews_is_limited_per_member(): void
    {
        $member = User::factory()->create();
        $film = Film::factory()->create();

        foreach (range(1, 30) as $i) {
            $this->actingAs($member)->post(route('films.review', $film), ['rating' => 4, 'body' => "Take {$i}"])->assertRedirect();
        }

        $this->actingAs($member)->post(route('films.review', $film), ['rating' => 4, 'body' => 'Spam'])->assertTooManyRequests();
    }

    public function test_the_limit_page_is_friendly(): void
    {
        foreach (range(1, 40) as $i) {
            $this->get(route('search', ['q' => 'x']));
        }

        $this->get(route('search', ['q' => 'x']))
            ->assertTooManyRequests()
            ->assertSee('Slow down a little');
    }
}
