<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Admin;
use App\Models\FilmList;
use App\Models\Review;
use App\Models\User;
use Database\Seeders\AdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['filmfolio.admin' => [
            'name' => 'Test Admin',
            'username' => 'TestAdmin',
            'email' => 'Boss@Example.com',
            'password' => 'secret-pass-123',
        ]]);
    }

    public function test_seeder_builds_demo_data_with_historical_activity_dates(): void
    {
        config(['services.tmdb.key' => null]);
        Http::preventStrayRequests();

        $this->seed();

        $this->assertModelExists(Admin::where('email', 'boss@example.com')->firstOrFail());
        $this->assertDatabaseMissing('users', ['email' => 'boss@example.com']);
        $this->assertTrue(Activity::where('created_at', '<', now()->subDay())->exists());
        $this->assertTrue(FilmList::official()->exists());
        $this->assertTrue(User::where('is_featured', true)->exists());
        $this->assertSame(
            (int) Review::sum('agrees') + (int) Review::sum('disagrees'),
            DB::table('review_votes')->count(),
        );
    }

    public function test_admin_seeder_creates_the_admin_from_config(): void
    {
        $this->seed(AdminSeeder::class);

        $admin = Admin::where('username', 'TestAdmin')->sole();
        $this->assertSame('Test Admin', $admin->name);
        $this->assertSame('boss@example.com', $admin->email);
        $this->assertTrue(Hash::check('secret-pass-123', $admin->password));
    }

    public function test_admin_seeder_updates_instead_of_duplicating(): void
    {
        $this->seed(AdminSeeder::class);
        config(['filmfolio.admin.password' => 'changed-pass-456']);

        $this->seed(AdminSeeder::class);

        $this->assertSame(1, Admin::count());
        $this->assertTrue(Hash::check('changed-pass-456', Admin::where('username', 'TestAdmin')->sole()->password));
    }

    public function test_admin_seeder_keeps_an_existing_password_when_none_is_configured(): void
    {
        $this->seed(AdminSeeder::class);
        config(['filmfolio.admin.password' => null]);

        $this->seed(AdminSeeder::class);

        $this->assertTrue(Hash::check('secret-pass-123', Admin::where('username', 'TestAdmin')->sole()->password));
    }
}
