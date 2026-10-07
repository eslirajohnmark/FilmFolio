<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('film_lists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('visibility')->default('public');
            $table->string('cover')->nullable();
            $table->integer('film_count')->default(0);
            $table->timestamps();
        });

        Schema::create('list_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('film_list_id')->constrained()->onDelete('cascade');
            $table->foreignId('film_id')->constrained()->onDelete('cascade');
            $table->integer('position')->default(0);
            $table->unique(['film_list_id', 'film_id']);
        });

        Schema::create('watchlists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('film_id')->constrained()->onDelete('cascade');
            $table->unique(['user_id', 'film_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('watchlists');
        Schema::dropIfExists('list_items');
        Schema::dropIfExists('film_lists');
    }
};
