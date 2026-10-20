#!/bin/sh
# FilmFolio container start-up.
#   1. wait until MySQL accepts connections
#   2. (app container only, RUN_MIGRATIONS=true) migrate, create/update the admin,
#      and import films from TMDB if the catalogue is empty
#   3. cache config, routes, views and events
#   4. run the container's command (Apache, queue worker or scheduler)
set -e
cd /var/www/html

if [ ! -f .env ]; then
    echo "FilmFolio: no .env found. Mount it with ./.env:/var/www/html/.env:ro (see docker-compose.yml)." >&2
    exit 1
fi

# Runs PHP with Laravel booted, so the DB_* settings are read exactly as the app reads
# them: from .env, with the container's environment variables taking precedence.
laravel_php() {
    php -r 'require "vendor/autoload.php"; $app = require "bootstrap/app.php"; $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap(); '"$1"
}

echo "FilmFolio: waiting for the database at ${DB_HOST}..."
tries=0
until laravel_php 'try { DB::connection()->getPdo(); } catch (Throwable $e) { fwrite(STDERR, "FilmFolio: ".$e->getMessage().PHP_EOL); exit(1); }' 2>/dev/null; do
    tries=$((tries + 1))
    if [ "$tries" -ge 60 ]; then
        laravel_php 'try { DB::connection()->getPdo(); } catch (Throwable $e) { fwrite(STDERR, "FilmFolio: ".$e->getMessage().PHP_EOL); }' || true
        echo "FilmFolio: database still unreachable after 2 minutes, giving up. Is MySQL running, and are the DB_* values in .env right?" >&2
        exit 1
    fi
    sleep 2
done

if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
    php artisan migrate --force
    php artisan db:seed --class=AdminSeeder --force

    films=$(laravel_php 'try { echo DB::table("films")->count(); } catch (Throwable $e) { echo 0; }')
    if [ "$films" = "0" ]; then
        echo "FilmFolio: no films yet, importing from TMDB..."
        php artisan films:sync || echo "FilmFolio: TMDB import failed (check TMDB_API_KEY); continuing without films." >&2
    fi
fi

php artisan optimize

exec "$@"
