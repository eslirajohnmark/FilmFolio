<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Suspensions can now expire (suspended_until; null = until lifted),
     * bans are permanent, and admins can feature members.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('suspended_until')->nullable()->after('suspended_at');
            $table->timestamp('banned_at')->nullable()->after('suspended_until');
            $table->string('moderation_reason', 500)->nullable()->after('banned_at');
            $table->boolean('is_featured')->default(false)->after('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['suspended_until', 'banned_at', 'moderation_reason', 'is_featured']);
        });
    }
};
