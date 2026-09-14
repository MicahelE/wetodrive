<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Drive access is a tick-box on Google's consent screen, and 53 of 138 sign-ins
 * in the fortnight to 14 Sep came back without it. Nobody noticed, so those
 * users could start transfers that pulled every file from WeTransfer in full
 * before Drive refused them. #603 lost 2.52GB and 35 minutes that way, three
 * times over.
 */
class DriveScopeTest extends TestCase
{
    use RefreshDatabase;

    private const DRIVE = 'https://www.googleapis.com/auth/drive.file';
    private const LINK = 'https://we.tl/t-ABC123';

    public function test_a_granted_scope_list_allows_transfers(): void
    {
        $user = User::factory()->create(['google_scopes' => 'email profile ' . self::DRIVE . ' openid']);

        $this->assertTrue($user->hasDriveAccess());
    }

    public function test_a_scope_list_without_drive_is_refused(): void
    {
        $user = User::factory()->create(['google_scopes' => 'email profile openid']);

        $this->assertFalse($user->hasDriveAccess());
    }

    /**
     * Everyone who signed in before this shipped has no recorded scopes. They
     * must keep working: unknown is not the same as missing.
     */
    public function test_an_unrecorded_scope_list_is_treated_as_granted(): void
    {
        $this->assertTrue(User::factory()->create(['google_scopes' => null])->hasDriveAccess());
        $this->assertTrue(User::factory()->create(['google_scopes' => ''])->hasDriveAccess());
    }

    public function test_a_transfer_is_refused_before_anything_is_downloaded(): void
    {
        $user = User::factory()->create([
            'subscription_tier' => 'free',
            'google_scopes' => 'email profile openid',
        ]);

        $this->actingAs($user)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->postJson('/transfer', ['wetransfer_url' => self::LINK])
            ->assertStatus(403)
            ->assertJson(['success' => false, 'needs_reconnect' => true]);
    }

    public function test_the_refusal_comes_before_the_transfer_limit_message(): void
    {
        // Out of transfers AND missing the scope: the useful message is the one
        // they can act on, not an upsell for a transfer that cannot land.
        $user = User::factory()->create([
            'subscription_tier' => 'free',
            'total_transfers' => 5,
            'google_scopes' => 'email profile openid',
        ]);

        $this->actingAs($user)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->postJson('/transfer', ['wetransfer_url' => self::LINK])
            ->assertStatus(403)
            ->assertJson(['needs_reconnect' => true]);
    }

    public function test_the_homepage_warns_someone_missing_the_scope(): void
    {
        $this->actingAs(User::factory()->create(['google_scopes' => 'email profile openid']))
            ->get('/')
            ->assertOk()
            ->assertSee('Google Drive access is not granted yet');
    }

    public function test_the_homepage_stays_quiet_for_everyone_else(): void
    {
        $this->actingAs(User::factory()->create(['google_scopes' => 'email ' . self::DRIVE]))
            ->get('/')
            ->assertOk()
            ->assertDontSee('Google Drive access is not granted yet');

        $this->actingAs(User::factory()->create(['google_scopes' => null]))
            ->get('/')
            ->assertOk()
            ->assertDontSee('Google Drive access is not granted yet');
    }
}
