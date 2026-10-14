<?php

namespace Tests\Feature;

use App\Livewire\ReviewVote;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ToastNotificationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_logging_in_shows_a_green_toast(): void
    {
        $user = User::factory()->create();

        $this->followingRedirects()
            ->post('/login', ['login' => $user->email, 'password' => 'password'])
            ->assertSee('<div class="toast success" role="status">Login successful.</div>', false);
    }

    public function test_wrong_credentials_show_a_red_toast(): void
    {
        $user = User::factory()->create();

        $this->from('/login')->followingRedirects()
            ->post('/login', ['login' => $user->email, 'password' => 'wrong-password'])
            ->assertSee('<div class="toast error" role="alert">'.e(trans('auth.failed')).'</div>', false);
    }

    public function test_error_flashes_show_a_red_toast(): void
    {
        $this->withSession(['error' => 'Only public lists can be featured.'])->get('/')
            ->assertSee('<div class="toast error" role="alert">Only public lists can be featured.</div>', false);
    }

    public function test_pages_that_explain_the_status_inline_skip_the_toast(): void
    {
        $this->withSession(['status' => 'Reset link on its way.'])->get('/forgot-password')
            ->assertSee('Reset link on its way.')
            ->assertDontSee('class="toast success"', false);
    }

    public function test_voting_on_your_own_review_raises_a_red_toast(): void
    {
        $review = Review::factory()->create();

        Livewire::actingAs($review->user)
            ->test(ReviewVote::class, ['review' => $review])
            ->call('agree')
            ->assertDispatched('notify', type: 'error');
    }
}
