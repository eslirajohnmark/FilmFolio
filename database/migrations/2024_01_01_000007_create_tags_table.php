<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->integer('use_count')->default(0);
            $table->timestamps();
        });

        Schema::create('film_tag', function (Blueprint $table) {
            $table->foreignId('film_id')->constrained()->onDelete('cascade');
            $table->foreignId('tag_id')->constrained()->onDelete('cascade');
            $table->primary(['film_id', 'tag_id']);
        });

        Schema::create('diary_entry_tag', function (Blueprint $table) {
            $table->foreignId('diary_entry_id')->constrained()->onDelete('cascade');
            $table->foreignId('tag_id')->constrained()->onDelete('cascade');
            $table->primary(['diary_entry_id', 'tag_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('diary_entry_tag');
        Schema::dropIfExists('film_tag');
        Schema::dropIfExists('tags');
    }
};
