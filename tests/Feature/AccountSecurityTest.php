<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AccountSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_suspended_users_are_logged_out_on_their_next_request(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get(route('diary.index'))->assertOk();

        $user->forceFill(['suspended_at' => now()])->save();

        $this->get(route('diary.index'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('login');
        $this->assertGuest();
    }

    public function test_registration_can_never_create_an_admin(): void
    {
        $this->post('/register', [
            'name' => 'Sneaky',
            'username' => 'sneaky',
            'email' => 'sneaky@example.com',
            'password' => 'John123!',
            'password_confirmation' => 'John123!',
            'role' => 'admin',
        ]);

        $this->assertModelExists(User::where('email', 'sneaky@example.com')->firstOrFail());
        $this->assertSame(0, Admin::count());
    }

    public function test_avatar_file_is_removed_when_the_account_is_deleted(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $this->actingAs($user)->patch('/settings', [
            'name' => $user->name,
            'email' => $user->email,
            'avatar' => UploadedFile::fake()->image('me.jpg'),
        ])->assertSessionHasNoErrors();

        $path = $user->fresh()->avatar;
        Storage::disk('public')->assertExists($path);

        $this->actingAs($user)->delete('/settings', ['password' => 'password']);

        Storage::disk('public')->assertMissing($path);
    }
}
