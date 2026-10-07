<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Seeded administrator
    |--------------------------------------------------------------------------
    |
    | The admin account created by `php artisan db:seed`. Keep the real
    | password in .env only — never commit it. If no password is set, the
    | seeder generates a random one and prints it once.
    |
    */

    'admin' => [
        'name' => env('ADMIN_NAME', 'Administrator'),
        'username' => env('ADMIN_USERNAME', 'Admin'),
        'email' => env('ADMIN_EMAIL', 'admin@filmfolio.com'),
        'password' => env('ADMIN_PASSWORD'),
    ],

];
