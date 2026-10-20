<?php

namespace Tests\Feature;

use App\Livewire\FollowButton;
use App\Models\Film;
use App\Models\Review;
use App\Models\User;
use App\Notifications\NewFollower;
use App\Notifications\NewReviewComment;
use App\Notifications\WelcomeToFilmFolio;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class EmailNotificationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_members_get_a_welcome_email(): void
    {
        Notification::fake();

        $this->post('/register', [
            'name' => 'New Member',
            'username' => 'newmember',
            'email' => 'new@example.com',
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
        ]);

        Notification::assertSentTo(User::where('username', 'newmember')->sole(), WelcomeToFilmFolio::class);
    }

    public function test_members_are_emailed_about_new_followers(): void
    {
        Notification::fake();
        $follower = User::factory()->create(['username' => 'fan']);
        $member = User::factory()->create();

        Livewire::actingAs($follower)->test(FollowButton::class, ['user' => $member])->call('toggle');

        Notification::assertSentTo($member, NewFollower::class, function (NewFollower $notification) use ($member) {
            $this->assertSame(['mail'], $notification->via($member));
            $this->assertStringContainsString('@fan', $notification->toMail($member)->subject);

            return true;
        });
    }

    public function test_review_authors_are_emailed_about_comments_but_not_their_own(): void
    {
        Notification::fake();
        $author = User::factory()->create();
        $review = Review::factory()->for($author)->for(Film::factory()->create(['title' => 'Night Shift']))->create();

        $this->actingAs($author)->post(route('reviews.comments.store', $review), ['body' => 'My own note']);
        Notification::assertNothingSent();

        $this->actingAs(User::factory()->create())->post(route('reviews.comments.store', $review), ['body' => 'Great take!']);

        Notification::assertSentTo($author, NewReviewComment::class, fn (NewReviewComment $notification) => str_contains($notification->toMail($author)->subject, 'Night Shift'));
    }

    public function test_members_can_turn_activity_emails_off(): void
    {
        $member = User::factory()->create();

        $this->actingAs($member)->patch(route('profile.notifications'))->assertRedirect(route('profile.edit'));

        $this->assertFalse($member->fresh()->email_notifications);
        $this->assertSame([], (new NewFollower(User::factory()->create()))->via($member->fresh()));
    }

    public function test_activity_emails_are_queued_so_pages_stay_fast(): void
    {
        foreach ([WelcomeToFilmFolio::class, NewFollower::class, NewReviewComment::class] as $notification) {
            $this->assertContains(ShouldQueue::class, class_implements($notification));
        }
    }
}
