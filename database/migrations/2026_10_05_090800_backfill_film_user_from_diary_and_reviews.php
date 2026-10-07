<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Existing diary entries and ratings mean the member has watched the film;
     * a liked diary entry means they liked it.
     */
    public function up(): void
    {
        $now = now();

        DB::table('diary_entries')
            ->select('user_id', 'film_id', DB::raw('MAX(liked) AS liked'))
            ->groupBy('user_id', 'film_id')
            ->orderBy('user_id')
            ->orderBy('film_id')
            ->chunk(500, function ($rows) use ($now) {
                DB::table('film_user')->insertOrIgnore($rows->map(fn ($row) => [
                    'user_id' => $row->user_id,
                    'film_id' => $row->film_id,
                    'watched' => true,
                    'liked' => (bool) $row->liked,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all());
            });

        DB::table('reviews')
            ->select('user_id', 'film_id')
            ->orderBy('id')
            ->chunk(500, function ($rows) use ($now) {
                DB::table('film_user')->insertOrIgnore($rows->map(fn ($row) => [
                    'user_id' => $row->user_id,
                    'film_id' => $row->film_id,
                    'watched' => true,
                    'liked' => false,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all());
            });
    }

    /**
     * Backfilled rows can't be told apart from real ones, so this is a no-op.
     */
    public function down(): void
    {
        //
    }
};
