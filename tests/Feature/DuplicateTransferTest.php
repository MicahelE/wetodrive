<?php

namespace Tests\Feature;

use App\Http\Controllers\StreamProgressController;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * One transfer at a time. Resubmitting while a transfer runs used to re-download
 * and re-upload the whole thing: #518 paid the egress on the same 2.97GB link
 * four times over to deliver it once.
 */
class DuplicateTransferTest extends TestCase
{
    use RefreshDatabase;

    private const LINK = 'https://we.tl/t-AbCdEf1234';

    private function running(User $user): void
    {
        Cache::put("active_transfer_{$user->id}", 'transfer_abc', 900);
        StreamProgressController::updateProgress('transfer_abc', 1, 100, 'rushes');
    }

    public function test_a_second_transfer_is_refused_while_one_runs(): void
    {
        $user = User::factory()->create(['subscription_tier' => 'free']);
        $this->running($user);

        $this->actingAs($user)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->postJson('/transfer', ['wetransfer_url' => self::LINK])
            ->assertStatus(409)
            ->assertJson(['success' => false]);
    }

    public function test_the_refusal_happens_before_the_transfer_limit_check(): void
    {
        // Out of free transfers AND already running: the message must be the one
        // that tells them to wait, not an upsell for a transfer we won't start.
        $user = User::factory()->create([
            'subscription_tier' => 'free',
            'total_transfers' => 5,
        ]);
        $this->running($user);

        $this->actingAs($user)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->postJson('/transfer', ['wetransfer_url' => self::LINK])
            ->assertStatus(409)
            ->assertJsonFragment(['error' => 'You already have a transfer running. Wait for it to finish, then start the next one.']);
    }

    public function test_a_finished_transfer_does_not_block_the_next_one(): void
    {
        $user = User::factory()->create(['subscription_tier' => 'free']);
        Cache::put("active_transfer_{$user->id}", 'transfer_abc', 900);
        // Pointer left behind, progress gone: that is a finished transfer.

        $this->assertNull(StreamProgressController::activeTransferFor($user->id));

        $response = $this->actingAs($user)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->postJson('/transfer', ['wetransfer_url' => self::LINK]);

        $this->assertNotSame(409, $response->status(), 'a stale pointer must not lock the user out');
    }
}
