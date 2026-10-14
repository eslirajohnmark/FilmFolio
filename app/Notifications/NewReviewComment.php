<?php

namespace App\Notifications;

use App\Models\ReviewComment;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

/**
 * "@someone commented on your review of <film>". Only for members who keep email notifications on.
 */
class NewReviewComment extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public ReviewComment $comment) {}

    /**
     * @return list<string>
     */
    public function via(User $notifiable): array
    {
        return $notifiable->email_notifications ? ['mail'] : [];
    }

    public function toMail(User $notifiable): MailMessage
    {
        $commenter = $this->comment->user;
        $handle = '@'.($commenter->username ?? $commenter->name);
        $film = $this->comment->review->film;

        return (new MailMessage)
            ->subject("{$handle} commented on your review of {$film->title}")
            ->greeting('Hi '.($notifiable->name ?: $notifiable->username).',')
            ->line("{$handle} commented on your review of {$film->title}:")
            ->line('“'.Str::limit($this->comment->body, 300).'”')
            ->action('Read and reply', route('reviews.show', $this->comment->review).'#comments')
            ->line('You can turn these emails off in Settings.')
            ->salutation('Happy watching, the FilmFolio team');
    }
}
