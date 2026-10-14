<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * "@someone started following you". Only for members who keep email notifications on.
 */
class NewFollower extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public User $follower) {}

    /**
     * @return list<string>
     */
    public function via(User $notifiable): array
    {
        return $notifiable->email_notifications ? ['mail'] : [];
    }

    public function toMail(User $notifiable): MailMessage
    {
        $handle = '@'.($this->follower->username ?? $this->follower->name);

        return (new MailMessage)
            ->subject("{$handle} started following you on FilmFolio")
            ->greeting('Hi '.($notifiable->name ?: $notifiable->username).',')
            ->line("{$handle} is now following you, so your reviews and diary will show up in their feed.")
            ->action("See {$handle}'s profile", route('profile.show', $this->follower))
            ->line('You can turn these emails off in Settings.')
            ->salutation('Happy watching, the FilmFolio team');
    }
}
