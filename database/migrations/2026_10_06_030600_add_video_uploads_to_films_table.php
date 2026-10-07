<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Admins can upload trailer and teaser video files (stored on the public disk)
     * as an alternative to a YouTube trailer.
     */
    public function up(): void
    {
        Schema::table('films', function (Blueprint $table) {
            $table->string('trailer_path')->nullable()->after('trailer_key');
            $table->string('teaser_path')->nullable()->after('trailer_path');
        });
    }

    public function down(): void
    {
        Schema::table('films', function (Blueprint $table) {
            $table->dropColumn(['trailer_path', 'teaser_path']);
        });
    }
};
