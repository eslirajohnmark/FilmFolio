<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A member's "favourite four" films shown on their profile.
     */
    public function up(): void
    {
        Schema::create('favorite_films', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('film_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('position')->default(0);

            $table->unique(['user_id', 'film_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('favorite_films');
    }
};
