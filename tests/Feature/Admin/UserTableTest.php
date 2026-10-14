<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\UserTable;
use App\Models\Admin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class UserTableTest extends TestCase
{
    use RefreshDatabase;

    public function test_admins_can_filter_members_by_status(): void
    {
        $admin = Admin::factory()->create(['username' => 'boss']);
        User::factory()->create(['username' => 'goodgail']);
        User::factory()->create(['username' => 'suspendedsam', 'suspended_at' => now(), 'suspended_until' => now()->addDay()]);
        User::factory()->create(['username' => 'bannedbob', 'banned_at' => now()]);

        $this->actingAsAdmin($admin);
        $table = Livewire::test(UserTable::class);

        $table->set('status', 'suspended')->assertSee('suspendedsam')->assertDontSee('goodgail')->assertDontSee('bannedbob');
        $table->set('status', 'banned')->assertSee('bannedbob')->assertDontSee('suspendedsam')->assertDontSee('goodgail');
        $table->set('status', 'active')->assertSee('goodgail')->assertDontSee('suspendedsam')->assertDontSee('bannedbob');
    }

    public function test_an_expired_suspension_counts_as_active(): void
    {
        $admin = Admin::factory()->create();
        User::factory()->create(['username' => 'servedtime', 'suspended_at' => now()->subWeek(), 'suspended_until' => now()->subDay()]);

        $this->actingAsAdmin($admin);

        Livewire::test(UserTable::class)
            ->set('status', 'active')
            ->assertSee('servedtime');
    }

    public function test_non_admins_can_not_load_the_table(): void
    {
        Livewire::actingAs(User::factory()->create())
            ->test(UserTable::class)
            ->assertForbidden();
    }
}
