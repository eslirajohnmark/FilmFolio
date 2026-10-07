<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        ResetPassword::toMailUsing(function (User $user, string $token): MailMessage {
            $minutes = config('auth.passwords.'.config('auth.defaults.passwords').'.expire');

            return (new MailMessage)
                ->subject('Reset your FilmFolio password')
                ->greeting('Hi '.($user->name ?: $user->username).',')
                ->line('We received a request to reset the password for your FilmFolio account (@'.$user->username.').')
                ->action('Reset Password', route('password.reset', [
                    'token' => $token,
                    'email' => $user->getEmailForPasswordReset(),
                ]))
                ->line("This link will expire in {$minutes} minutes and can only be used once.")
                ->line("If you didn't ask to reset your password, you can ignore this email. Your password won't change.")
                ->salutation('Happy watching, the FilmFolio team');
        });
    }
}
