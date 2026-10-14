<?php

namespace Database\Seeders;

use App\Models\Activity;
use App\Models\Admin;
use App\Models\DiaryEntry;
use App\Models\Film;
use App\Models\FilmList;
use App\Models\Follow;
use App\Models\Review;
use App\Models\ReviewComment;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // -------- Admin (from ADMIN_* in .env, in the `admins` table) + demo members --------
        $this->call(AdminSeeder::class);
        $admin = Admin::where('email', Str::lower(config('filmfolio.admin.email')))->firstOrFail();

        $users = User::factory(30)->create();
        $allUsers = $users;

        // -------- Films from TMDB (fallback to fake) --------
        if (config('services.tmdb.key')) {
            Artisan::call('films:sync');
        }

        if (Film::count() < 6) {
            Film::factory(20)->create();
        }

        $films = Film::all();
        $tagPool = [
            'noir', 'slow-burn', 'masterpiece', 'rewatchable', 'indie',
            'sci-fi', 'mind-bender', 'feel-good', 'overrated',
            'underrated', 'atmospheric',
        ];

        // -------- Follow graph --------
        foreach ($allUsers as $user) {
            foreach ($allUsers->random(rand(3, 10)) as $target) {
                if ($target->id === $user->id) {
                    continue;
                }
                Follow::firstOrCreate([
                    'follower_id' => $user->id,
                    'following_id' => $target->id,
                ]);
            }
        }

        // -------- Diary entries + reviews + activities --------
        foreach ($allUsers as $user) {
            $watched = $films->random(min(rand(5, 15), $films->count()));

            foreach ($watched as $film) {
                $rating = collect([1, 1.5, 2, 2.5, 3, 3.5, 4, 4.5, 5])->random();

                $entry = DiaryEntry::create([
                    'user_id' => $user->id,
                    'film_id' => $film->id,
                    'watched_on' => now()->subDays(rand(0, 400)),
                    'rating' => $rating,
                    'review' => rand(1, 3) === 1
                        ? 'A short review about this film.'
                        : null,
                    'rewatch' => rand(1, 5) === 1,
                    'liked' => rand(1, 3) === 1,
                ]);

                // Attach 1–3 random tags
                $tagIds = [];
                foreach (collect($tagPool)->random(rand(1, 3)) as $name) {
                    $tag = Tag::findOrCreate($name);
                    $tag->incrementUse();
                    $tagIds[] = $tag->id;
                }
                $entry->tags()->sync($tagIds);
                $film->tags()->syncWithoutDetaching($tagIds);

                // Mirror to reviews table
                Review::create([
                    'user_id' => $user->id,
                    'film_id' => $film->id,
                    'rating' => $rating,
                    'body' => $entry->review,
                ]);

                // Log activity
                (new Activity)->forceFill([
                    'user_id' => $user->id,
                    'type' => $entry->review ? 'reviewed' : 'watched',
                    'subject_type' => Film::class,
                    'subject_id' => $film->id,
                    'meta' => [
                        'rating' => $rating,
                        'body' => $entry->review,
                        'rewatch' => $entry->rewatch,
                    ],
                    'created_at' => $entry->watched_on,
                    'updated_at' => $entry->watched_on,
                ])->save();

                // Watched / liked state
                $user->markWatched($film);
                if ($entry->liked) {
                    $user->films()->updateExistingPivot($film->id, ['liked' => true]);
                }
            }

            // Favourite four, chosen from what they watched
            foreach ($watched->shuffle()->take(4)->values() as $position => $film) {
                $user->favoriteFilms()->attach($film->id, ['position' => $position + 1]);
            }
        }

        // -------- Comments on written reviews --------
        Review::whereNotNull('body')->inRandomOrder()->take(30)->get()->each(function (Review $review) use ($allUsers) {
            foreach ($allUsers->random(rand(1, 3)) as $commenter) {
                ReviewComment::factory()->for($review)->for($commenter)->create();
            }
        });

        // -------- Agree / disagree votes --------
        Review::all()->each(function (Review $review) use ($users) {
            foreach ($users->where('id', '!=', $review->user_id)->random(rand(0, 6)) as $voter) {
                $review->castVote($voter, rand(1, 4) === 1 ? Review::DISAGREE : Review::AGREE);
            }
        });

        // -------- Member lists, with likes --------
        foreach ($users->random(10) as $owner) {
            $list = FilmList::create([
                'user_id' => $owner->id,
                'name' => fake()->randomElement(['Weekend Watchlist', 'All-Time Favourites', 'Hidden Gems', 'Comfort Films', 'Mind Benders']).' #'.$owner->id,
                'description' => fake()->sentence(),
                'visibility' => rand(1, 5) === 1 ? 'private' : 'public',
            ]);
            $list->films()->attach($films->random(min(6, $films->count()))->values()->mapWithKeys(fn ($film, $i) => [$film->id => ['position' => $i + 1]]));
            $list->likes()->attach($users->where('id', '!=', $owner->id)->random(rand(0, 8))->pluck('id'));
        }
        FilmList::public()->inRandomOrder()->take(2)->get()->each->forceFill(['is_featured' => true])->each->save();

        // -------- Crew Picks: official lists for the top genres --------
        Film::whereNotNull('genre')->distinct()->pluck('genre')->take(3)->each(function (string $genre) use ($admin) {
            $list = new FilmList([
                'name' => "Essential {$genre}",
                'description' => "The FilmFolio crew's favourite {$genre} films.",
                'genre' => $genre,
                'visibility' => 'public',
            ]);
            $list->forceFill(['admin_id' => $admin->id, 'is_official' => true])->save();

            $topRated = Film::where('genre', $genre)->withAvg('reviews', 'rating')->orderByDesc('reviews_avg_rating')->take(8)->pluck('id');
            $list->films()->attach($topRated->values()->mapWithKeys(fn ($id, $i) => [$id => ['position' => $i + 1]]));
        });

        // -------- Featured members --------
        $users->random(3)->each->forceFill(['is_featured' => true])->each->save();
    }
}
