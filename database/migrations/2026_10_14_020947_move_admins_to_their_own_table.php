<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Administrators move out of `users` into their own `admins` table (with their
 * own login guard). Official lists they created are re-owned through `admin_id`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admins', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('username')->unique();
            $table->string('email')->unique();
            $table->string('password');
            $table->rememberToken();
            $table->timestamp('last_login_at')->nullable();
            $table->timestamps();
        });

        Schema::table('film_lists', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->change();
            $table->foreignId('admin_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
        });

        foreach (DB::table('users')->where('role', 'admin')->get() as $user) {
            $adminId = DB::table('admins')->insertGetId([
                'name' => $user->name,
                'username' => $user->username ?? 'admin'.$user->id,
                'email' => $user->email,
                'password' => $user->password,
                'remember_token' => $user->remember_token,
                'created_at' => $user->created_at,
                'updated_at' => now(),
            ]);

            DB::table('film_lists')->where('user_id', $user->id)->update(['user_id' => null, 'admin_id' => $adminId]);
            DB::table('sessions')->where('user_id', $user->id)->delete();
            DB::table('users')->where('id', $user->id)->delete();
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['user', 'admin'])->default('user')->after('avatar');
        });

        foreach (DB::table('admins')->get() as $admin) {
            $userId = DB::table('users')->insertGetId([
                'name' => $admin->name,
                'username' => $admin->username,
                'email' => $admin->email,
                'password' => $admin->password,
                'remember_token' => $admin->remember_token,
                'role' => 'admin',
                'email_verified_at' => now(),
                'created_at' => $admin->created_at,
                'updated_at' => now(),
            ]);

            DB::table('film_lists')->where('admin_id', $admin->id)->update(['user_id' => $userId]);
        }

        DB::table('film_lists')->whereNull('user_id')->delete();

        Schema::table('film_lists', function (Blueprint $table) {
            $table->dropConstrainedForeignId('admin_id');
            $table->foreignId('user_id')->nullable(false)->change();
        });

        Schema::dropIfExists('admins');
    }
};
