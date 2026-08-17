<?php

namespace Tests\Feature;

use App\Http\Controllers\StreamProgressController;
use App\Http\Controllers\TransferController;
use App\Models\Transfer;
use App\Models\User;
use App\Services\StreamTransferService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * A multi-file WeTransfer used to arrive as one zip, which Google Drive cannot
 * open, preview or search inside: delivered, but not usable. These cover the
 * per-file import that replaces it, and the rules that go with it — one link
 * costs one transfer, and a file that fails does not discard the ones that
 * already landed.
 */
class MultiFileTransferTest extends TestCase
{
    use RefreshDatabase;

    /** Invoke a private method on the controller. */
    private function invokePrivate(string $method, array $args)
    {
        $controller = app(TransferController::class);
        $reflection = new \ReflectionMethod($controller, $method);
        $reflection->setAccessible(true);

        return $reflection->invokeArgs($controller, $args);
    }

    public function test_a_manifest_of_plain_files_is_kept(): void
    {
        $items = StreamTransferService::filterItems([
            ['id' => 'i1', 'name' => 'clip-a.mov', 'size' => 3145728, 'item_type' => 'file'],
            ['id' => 'i2', 'name' => 'notes.txt', 'size' => 2426, 'item_type' => 'file'],
        ]);

        $this->assertCount(2, $items);
        $this->assertSame(['id' => 'i1', 'name' => 'clip-a.mov', 'size' => 3145728], $items[0]);
    }

    public function test_a_folder_item_makes_the_whole_transfer_fall_back_to_the_archive(): void
    {
        // WeTransfer allows uploading whole folders, which have no single file
        // behind them. Importing only the loose files would silently drop the
        // rest, so the fallback is all-or-nothing.
        $items = StreamTransferService::filterItems([
            ['id' => 'i1', 'name' => 'clip-a.mov', 'size' => 10, 'item_type' => 'file'],
            ['id' => 'i2', 'name' => 'Footage', 'size' => 0, 'item_type' => 'folder'],
        ]);

        $this->assertSame([], $items, 'a folder item should force the archive path');
    }

    public function test_an_item_missing_an_id_falls_back_rather_than_guessing(): void
    {
        $this->assertSame([], StreamTransferService::filterItems([
            ['name' => 'mystery.mov', 'size' => 10, 'item_type' => 'file'],
        ]));
    }

    public function test_each_file_becomes_its_own_row_sharing_a_batch_with_its_own_drive_link(): void
    {
        $user = User::factory()->create();

        foreach ([['clip-a.mov', 3145728, 'd1'], ['clip-b.mov', 4194304, 'd2'], ['notes.txt', 2426, 'd3']] as [$name, $size, $drive]) {
            Transfer::create([
                'user_id' => $user->id,
                'batch_id' => 'batch-1',
                'filename' => $name,
                'file_size' => $size,
                'google_drive_id' => $drive,
                'transferred_at' => now(),
            ]);
        }

        $this->assertSame(3, $user->transfers()->count());
        $this->assertSame(['batch-1'], $user->transfers()->pluck('batch_id')->unique()->values()->all());

        $this->actingAs($user)->get('/subscription/manage')->assertOk()
            ->assertSee('clip-a.mov')
            ->assertSee('notes.txt')
            ->assertSee('https://drive.google.com/file/d/d3/view', false);
    }

    public function test_a_batch_costs_exactly_one_transfer_however_many_files_it_holds(): void
    {
        // Charging per file would exhaust a free plan on a single paste.
        $user = User::factory()->create(['total_transfers' => 0]);

        $this->invokePrivate('finishBatch', [
            $user, 'transfer_b1', 'folder-1', false,
            [
                ['filename' => 'a.mov', 'google_drive_id' => 'd1', 'size' => 1],
                ['filename' => 'b.mov', 'google_drive_id' => 'd2', 'size' => 1],
                ['filename' => 'c.txt', 'google_drive_id' => 'd3', 'size' => 1],
            ],
            [], 'Test transfer', 3,
        ]);

        $this->assertSame(1, $user->fresh()->total_transfers);
    }

    public function test_a_batch_where_everything_failed_costs_nothing_and_returns_the_trial(): void
    {
        $user = User::factory()->create(['total_transfers' => 0, 'has_used_trial_transfer' => true]);

        $this->invokePrivate('finishBatch', [
            $user, 'transfer_b2', null, true,
            [], [['filename' => 'a.mov', 'error' => 'boom']], 'Test transfer', 3,
        ]);

        $this->assertSame(0, $user->fresh()->total_transfers, 'a failed transfer must not consume quota');
        $this->assertFalse((bool) $user->fresh()->has_used_trial_transfer, 'the one-time trial should be handed back');

        $result = Cache::get('transfer_result_transfer_b2');
        $this->assertFalse($result['success']);
    }

    public function test_a_partly_failed_batch_still_reports_what_landed(): void
    {
        // The files already in Drive are real and must not be thrown away.
        $user = User::factory()->create(['total_transfers' => 0]);

        $this->invokePrivate('finishBatch', [
            $user, 'transfer_b3', 'folder-9', false,
            [['filename' => 'a.mov', 'google_drive_id' => 'd1', 'size' => 1]],
            [['filename' => 'b.mov', 'error' => 'boom']],
            'Test transfer', 2,
        ]);

        $result = Cache::get('transfer_result_transfer_b3');

        $this->assertTrue($result['success']);
        $this->assertCount(1, $result['files']);
        $this->assertCount(1, $result['failed']);
        $this->assertSame('https://drive.google.com/drive/folders/folder-9', $result['folder_url']);
        $this->assertSame(1, $user->fresh()->total_transfers);
    }

    public function test_a_single_file_batch_keeps_the_old_result_shape(): void
    {
        // The existing UI reads google_drive_id; a batch of one must not break it.
        $user = User::factory()->create();

        $this->invokePrivate('finishBatch', [
            $user, 'transfer_b4', null, false,
            [['filename' => 'only.mov', 'google_drive_id' => 'd-only', 'size' => 1]],
            [], 'only.mov', 1,
        ]);

        $this->assertSame('d-only', Cache::get('transfer_result_transfer_b4')['google_drive_id']);
    }

    public function test_the_result_is_readable_the_moment_the_status_says_completed(): void
    {
        // The SSE loop emits the complete event as soon as it sees the status, so
        // a result written afterwards arrives too late and the page shows
        // "Transfer Complete" with no files and no link. Observed for real.
        StreamProgressController::updateProgress('transfer_race', 0, 10, 'a.mov', 'uploading');

        StreamProgressController::completeTransfer('transfer_race', true, [
            'success' => true,
            'files' => [['filename' => 'a.mov', 'google_drive_id' => 'd1', 'size' => 10]],
        ]);

        $progress = Cache::get('transfer_progress_transfer_race');
        $result = Cache::get('transfer_result_transfer_race');

        $this->assertSame('completed', $progress['status']);
        $this->assertNotNull($result, 'the result must exist by the time the status flips');
        $this->assertCount(1, $result['files']);
    }

    public function test_progress_carries_the_position_in_the_batch(): void
    {
        StreamProgressController::updateProgress('transfer_p1', 500, 1000, 'clip-b.mov', 'uploading', 2, 3);

        $progress = Cache::get('transfer_progress_transfer_p1');

        $this->assertSame(2, $progress['fileIndex']);
        $this->assertSame(3, $progress['fileCount']);
    }

    public function test_progress_leaves_the_batch_fields_unset_for_a_plain_transfer(): void
    {
        StreamProgressController::updateProgress('transfer_p2', 500, 1000, 'one.mov', 'uploading');

        $progress = Cache::get('transfer_progress_transfer_p2');

        $this->assertNull($progress['fileIndex']);
        $this->assertNull($progress['fileCount']);
    }
}
