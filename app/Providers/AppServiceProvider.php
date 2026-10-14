<?php

namespace App\Providers;

use App\Models\User;
use App\Notifications\WelcomeToFilmFolio;
use App\Services\Recommendations;
use App\Services\ResendApiTransport;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
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
        $this->configureRateLimiting();

        Mail::extend('resend-api', fn (array $config) => new ResendApiTransport(
            (string) $config['key'],
            (int) ($config['timeout'] ?? 10),
        ));

        Event::listen(function (Registered $event): void {
            if ($event->user instanceof User) {
                $event->user->notify(new WelcomeToFilmFolio);
            }
        });

        // What a guest searched for keeps shaping their suggestions after they sign in or register.
        // (Login fires before the session id is regenerated, so it's still the guest's.)
        Event::listen(function (Login $event): void {
            if ($event->user instanceof User && request()->hasSession()) {
                app(Recommendations::class)->claimGuestSignals($event->user, request()->session()->getId());
            }
        });

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

    /**
     * Named rate limits, keyed by member when signed in and by IP address otherwise.
     * Over the limit, a request gets a 429 "Slow down a little" page.
     */
    protected function configureRateLimiting(): void
    {
        $key = fn (Request $request): string => $request->user()?->id ? 'user:'.$request->user()->id : 'ip:'.$request->ip();

        // Every page and Livewire click: generous, it only stops scripted floods.
        RateLimiter::for('global', fn (Request $request) => Limit::perMinute(240)->by($key($request)));

        // Sign-in attempts per IP (LoginRequest also locks a single username after 5 failures).
        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(10)->by($request->ip()));

        // New accounts per IP.
        RateLimiter::for('register', fn (Request $request) => Limit::perHour(10)->by($request->ip()));

        // Search also calls the TMDB API, so it is kept lower.
        RateLimiter::for('search', fn (Request $request) => Limit::perMinute(40)->by($key($request)));

        // Posting reviews, comments and list changes.
        RateLimiter::for('writes', fn (Request $request) => Limit::perMinute(30)->by($key($request)));
    }
}
