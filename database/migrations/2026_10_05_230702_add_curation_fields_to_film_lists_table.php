<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Official lists ("Crew Picks") are created by admins around a genre or theme;
     * featured lists are hand-picked by admins for the Lists page.
     */
    public function up(): void
    {
        Schema::table('film_lists', function (Blueprint $table) {
            $table->text('description')->nullable()->after('name');
            $table->boolean('is_official')->default(false)->after('visibility');
            $table->boolean('is_featured')->default(false)->after('is_official');
            $table->string('genre')->nullable()->after('is_featured');

            $table->index(['is_official', 'visibility']);
            $table->index(['is_featured', 'visibility']);
        });
    }

    public function down(): void
    {
        Schema::table('film_lists', function (Blueprint $table) {
            $table->dropIndex(['is_official', 'visibility']);
            $table->dropIndex(['is_featured', 'visibility']);
            $table->dropColumn(['description', 'is_official', 'is_featured', 'genre']);
        });
    }
};
