<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hints about which genres someone likes (searches, film views, genre filters),
 * used to personalise film suggestions. Guests are tracked by session until they sign in.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interest_signals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('session_key', 100)->nullable()->index();
            $table->string('genre')->index();
            $table->string('source', 20);
            $table->string('term', 100)->nullable();
            $table->unsignedTinyInteger('weight')->default(1);
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interest_signals');
    }
};
