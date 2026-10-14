<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent once, right after someone creates an account. Queued, so signing up stays fast.
 */
class WelcomeToFilmFolio extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @return list<string>
     */
    public function via(User $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Welcome to FilmFolio, @'.$notifiable->username.'!')
            ->greeting('Hi '.($notifiable->name ?: $notifiable->username).',')
            ->line('Your FilmFolio account is ready. Here is how to get started:')
            ->line('• Log the films you watch in your diary and rate them.')
            ->line('• Save the ones you want to see to your watchlist.')
            ->line('• Follow other members to see what they are watching.')
            ->action('Find a film', route('films.index'))
            ->line('The more you search, rate and watch, the better your "Suggested for you" picks get.')
            ->salutation('Happy watching, the FilmFolio team');
    }
}
