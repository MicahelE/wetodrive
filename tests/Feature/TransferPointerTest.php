<?php

namespace Tests\Feature;

use App\Http\Controllers\StreamProgressController as SPC;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * The pointer is written once and never refreshed, so it has to outlive the
 * transfer. When it expired at 15 minutes, a 4h40m batch on 2 Sep left its owner
 * staring at a dead progress bar and being told the transfer was not theirs.
 */
class TransferPointerTest extends TestCase
{
    use RefreshDatabase;

    private const TID = 'transfer_abc';

    public function test_the_pointer_outlives_a_long_transfer(): void
    {
        SPC::markActiveTransfer(7, self::TID);

        // Travel past the old 900s TTL, still mid-transfer.
        $this->travel(3)->hours();
        SPC::updateProgress(self::TID, 50, 100, 'rushes'); // progress keeps renewing

        $this->assertSame(self::TID, SPC::activeTransferFor(7), 'pointer must survive a long transfer');
    }

    public function test_a_finished_transfer_stops_being_active_once_progress_expires(): void
    {
        SPC::markActiveTransfer(7, self::TID);
        SPC::updateProgress(self::TID, 100, 100, 'rushes');

        $this->assertSame(self::TID, SPC::activeTransferFor(7));

        // Progress is what decides liveness, and it is not renewed after the end.
        $this->travel(31)->minutes();

        $this->assertNull(SPC::activeTransferFor(7), 'a long-lived pointer must not keep a dead transfer active');
    }

    public function test_the_stream_refuses_a_transfer_whose_progress_is_gone(): void
    {
        $user = User::factory()->create();
        Cache::put("active_transfer_{$user->id}", self::TID, 86400); // pointer only, no progress

        $this->actingAs($user)
            ->get('/transfer/progress?transfer_id=' . self::TID)
            ->assertStatus(403);
    }

    public function test_the_stream_still_refuses_someone_elses_transfer(): void
    {
        $user = User::factory()->create();
        SPC::markActiveTransfer($user->id, self::TID);
        SPC::updateProgress(self::TID, 1, 100, 'rushes');

        $this->actingAs($user)
            ->get('/transfer/progress?transfer_id=transfer_someone_else')
            ->assertStatus(403);
    }
}
