<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Reviews move from "likes" to agree / disagree votes. Existing likes
     * become agree votes, so no data is lost.
     */
    public function up(): void
    {
        Schema::create('review_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('review_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->tinyInteger('vote');   // 1 = agree, -1 = disagree
            $table->timestamps();

            $table->unique(['review_id', 'user_id']);
        });

        $now = now();
        DB::table('review_likes')->orderBy('id')->chunk(500, function ($likes) use ($now) {
            DB::table('review_votes')->insertOrIgnore($likes->map(fn ($like) => [
                'review_id' => $like->review_id,
                'user_id' => $like->user_id,
                'vote' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ])->all());
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->renameColumn('likes', 'agrees');
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->unsignedInteger('disagrees')->default(0)->after('agrees');
        });

        // Counts must match real votes (older demo data stored made-up like counts).
        DB::table('reviews')->update([
            'agrees' => DB::raw('(SELECT COUNT(*) FROM review_votes WHERE review_votes.review_id = reviews.id AND review_votes.vote = 1)'),
        ]);

        Schema::dropIfExists('review_likes');
    }

    public function down(): void
    {
        Schema::create('review_likes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('review_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unique(['review_id', 'user_id']);
        });

        DB::table('review_votes')->where('vote', 1)->orderBy('id')->chunk(500, function ($votes) {
            DB::table('review_likes')->insertOrIgnore($votes->map(fn ($vote) => [
                'review_id' => $vote->review_id,
                'user_id' => $vote->user_id,
            ])->all());
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->dropColumn('disagrees');
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->renameColumn('agrees', 'likes');
        });

        Schema::dropIfExists('review_votes');
    }
};
