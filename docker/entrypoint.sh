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

echo "FilmFolio: waiting for the database at ${DB_HOST}:${DB_PORT:-3306}..."
tries=0
until php -r 'try { new PDO("mysql:host=".getenv("DB_HOST").";port=".(getenv("DB_PORT") ?: 3306).";dbname=".getenv("DB_DATABASE"), getenv("DB_USERNAME"), getenv("DB_PASSWORD")); } catch (Throwable $e) { exit(1); }'; do
    tries=$((tries + 1))
    if [ "$tries" -ge 60 ]; then
        echo "FilmFolio: database still unreachable after 2 minutes, giving up." >&2
        exit 1
    fi
    sleep 2
done

if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
    php artisan migrate --force
    php artisan db:seed --class=AdminSeeder --force

    films=$(php -r 'try { $pdo = new PDO("mysql:host=".getenv("DB_HOST").";port=".(getenv("DB_PORT") ?: 3306).";dbname=".getenv("DB_DATABASE"), getenv("DB_USERNAME"), getenv("DB_PASSWORD")); echo (int) $pdo->query("SELECT COUNT(*) FROM films")->fetchColumn(); } catch (Throwable $e) { echo 0; }')
    if [ "$films" = "0" ]; then
        echo "FilmFolio: no films yet, importing from TMDB..."
        php artisan films:sync || echo "FilmFolio: TMDB import failed (check TMDB_API_KEY); continuing without films." >&2
    fi
fi

php artisan optimize

exec "$@"
