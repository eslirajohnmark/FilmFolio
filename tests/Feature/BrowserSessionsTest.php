<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class BrowserSessionsTest extends TestCase
{
    use RefreshDatabase;

    private const FIREFOX_ON_WINDOWS = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:128.0) Gecko/20100101 Firefox/128.0';

    protected function setUp(): void
    {
        parent::setUp();

        config(['session.driver' => 'database']);
    }

    private function sessionFor(User $user, string $userAgent = self::FIREFOX_ON_WINDOWS): string
    {
        $id = Str::random(40);

        DB::table('sessions')->insert([
            'id' => $id,
            'user_id' => $user->id,
            'ip_address' => '203.0.113.7',
            'user_agent' => $userAgent,
            'payload' => base64_encode(serialize([])),
            'last_activity' => now()->subHour()->getTimestamp(),
        ]);

        return $id;
    }

    public function test_settings_lists_the_devices_a_member_is_signed_in_on(): void
    {
        $member = User::factory()->create();
        $this->sessionFor($member);
        $this->sessionFor(User::factory()->create(), 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) Safari/604.1');

        $this->actingAs($member)->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('Browser sessions')
            ->assertSee('Windows · Firefox')
            ->assertSee('203.0.113.7')
            ->assertDontSee('iOS · Safari');
    }

    public function test_a_member_can_sign_out_another_device(): void
    {
        $member = User::factory()->create();
        $other = $this->sessionFor($member);
        $rememberToken = $member->remember_token;

        $this->actingAs($member)->delete(route('sessions.destroy', $other))->assertRedirect();

        $this->assertDatabaseMissing('sessions', ['id' => $other]);
        $this->assertNotSame($rememberToken, $member->fresh()->remember_token);
    }

    public function test_a_member_can_not_sign_out_someone_elses_session(): void
    {
        $member = User::factory()->create();
        $someoneElses = $this->sessionFor(User::factory()->create());

        $this->actingAs($member)->delete(route('sessions.destroy', $someoneElses));

        $this->assertDatabaseHas('sessions', ['id' => $someoneElses]);
    }

    public function test_signing_out_every_other_device_needs_the_password(): void
    {
        $member = User::factory()->create();
        $first = $this->sessionFor($member);
        $second = $this->sessionFor($member);

        $this->actingAs($member)->delete(route('sessions.destroy-others'), ['password' => 'wrong-password'])
            ->assertSessionHasErrorsIn('logoutOtherSessions', 'password');
        $this->assertDatabaseHas('sessions', ['id' => $first]);

        $this->actingAs($member)->delete(route('sessions.destroy-others'), ['password' => 'password'])
            ->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('sessions', ['id' => $first]);
        $this->assertDatabaseMissing('sessions', ['id' => $second]);
    }

    public function test_admins_can_sign_a_member_out_everywhere(): void
    {
        $member = User::factory()->create();
        $session = $this->sessionFor($member);

        $this->actingAsAdmin()->delete(route('admin.accounts.sessions.destroy', $member))->assertRedirect();

        $this->assertDatabaseMissing('sessions', ['id' => $session]);
    }
}
