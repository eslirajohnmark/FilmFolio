<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AdminSeeder extends Seeder
{
    /**
     * Create or update the administrator (in the `admins` table) from config/filmfolio.php (ADMIN_* in .env).
     *
     * Safe to re-run: it updates the existing admin instead of creating a duplicate.
     * Without ADMIN_PASSWORD, a new admin gets a random password that is printed once;
     * an existing admin keeps their current password.
     */
    public function run(): Admin
    {
        $config = config('filmfolio.admin');
        $email = Str::lower($config['email']);

        $admin = Admin::where('email', $email)->orWhere('username', $config['username'])->first() ?? new Admin;
        $generatedPassword = null;

        $admin->forceFill([
            'name' => $config['name'],
            'username' => $config['username'],
            'email' => $email,
        ]);

        if ($config['password']) {
            $admin->password = $config['password'];
        } elseif (! $admin->exists) {
            $admin->password = $generatedPassword = Str::password(16);
        }

        $admin->save();

        if ($generatedPassword) {
            $this->command?->warn("ADMIN_PASSWORD is not set. Generated admin password (shown once): {$generatedPassword}");
        }

        return $admin;
    }
}
