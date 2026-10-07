# FilmFolio

Track, rate and share the films you watch: a diary of every viewing, star ratings and reviews, lists, a watchlist, and a social feed of the members you follow. Film data and trailers come from [TMDB](https://www.themoviedb.org/).

Built with Laravel 13, Livewire 4 and Vite. Custom CSS lives in `resources/css/modules`; Tailwind v3 is only used for the auth/settings forms and pagination.

## Setup

Requirements: PHP 8.3+, Composer, Node.js, and MySQL (or SQLite).

```sh
composer install
cp .env.example .env
php artisan key:generate
```

Edit `.env`:

- Set the `DB_*` values for your database.
- Set `TMDB_API_KEY` to a TMDB v3 API key ([get one here](https://www.themoviedb.org/settings/api)).
- Set `ADMIN_NAME`, `ADMIN_USERNAME`, `ADMIN_EMAIL` and `ADMIN_PASSWORD` for the administrator account. Quote the password if it contains special characters (`ADMIN_PASSWORD="..."`).

Then:

```sh
php artisan migrate --seed   # seeds an admin, 30 users, and imports films from TMDB
php artisan storage:link     # needed for avatar uploads
npm install
npm run build                # or `npm run dev` while developing
```

The admin account comes from the `ADMIN_*` values in `.env`; the password is never stored in the repository. If `ADMIN_PASSWORD` is empty, the seeder generates one and prints it once. To create or update just the admin (without demo data), run:

```sh
php artisan db:seed --class=AdminSeeder
```

## Importing films

Films are imported from TMDB by a command, never during a page request:

```sh
php artisan films:sync                          # 20 trending + 6 upcoming (teasers), with trailers
php artisan films:sync --trending=50 --upcoming=10
```

It is scheduled daily in `routes/console.php`. For the schedule to run, add the Laravel scheduler to cron (`* * * * * php artisan schedule:run`) or run `php artisan schedule:work` locally.

## Admin

The admin sign-in page isn't linked anywhere in the UI. Press <kbd>Ctrl</kbd>+<kbd>Shift</kbd>+<kbd>L</kbd> on any page to open it (`/admin/login`); signed-in admins go straight to the dashboard at `/admin`. Hiding the link is only a convenience — the page is still protected by admin credentials and login rate limiting.

The admin area has its own navigation:

- **Dashboard** — member counts, popular genres and decades, weekly / monthly / yearly statistics, and a 12-week activity chart.
- **Films** — add, edit and delete films; mark teasers; set YouTube trailers; import films from TMDB.
- **Lists** — create official lists ("Crew Picks") by genre or theme, optionally auto-filled with the genre's top-rated films; feature lists; remove inappropriate ones.
- **Accounts** — search and filter members, view their activity, reviews and lists, suspend (1 day to indefinitely), ban, reinstate, feature or delete accounts, and remove inappropriate reviews. Admin accounts are protected from moderation.

Suspended and banned members cannot sign in and are signed out on their next request.

In the `local` environment only, `/__dev-login` signs in as the first admin. Never deploy with `APP_ENV=local` or `APP_DEBUG=true`.

## Features

Members navigate **Films · Lists · Members · Teasers · Profile**.

- **Films:** browse by poster, search any film (TMDB results are imported on demand), filter and sort, popular this week, popular reviews this week.
- **Film page:** mark as watched, like, add to watchlist, quick half-star rating, add to lists or your favourite four, ratings histogram, diary logging, reviews.
- **Reviews:** agree or disagree with reviews, comment on them; edit or delete your own reviews and ratings. Long reviews also appear in the Journal.
- **Lists:** Featured Lists, Recently Popular (by likes), Crew Picks, your own public/private lists with descriptions; list pages show reviews of their films.
- **Members:** Featured Members, Popular Members and Most Active Reviewers; profiles with Activity, Films, Diary, Reviews, Ratings, Watchlist, Lists and Likes tabs.
- **Teasers:** upcoming films with an embedded trailer player and description.

Members sign in with their username or email.

## Tests

```sh
php artisan test
```

Tests run against an in-memory SQLite database (see `phpunit.xml`) and fake all TMDB requests.
