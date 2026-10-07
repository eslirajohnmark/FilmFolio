<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('diary_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('film_id')->constrained()->onDelete('cascade');
            $table->date('watched_on');
            $table->decimal('rating', 2, 1)->nullable();
            $table->text('review')->nullable();
            $table->boolean('rewatch')->default(false);
            $table->boolean('liked')->default(false);
            $table->timestamps();
            $table->index(['user_id', 'watched_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('diary_entries');
    }
};
