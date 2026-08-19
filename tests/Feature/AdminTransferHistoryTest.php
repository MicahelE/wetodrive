<?php

namespace Tests\Feature;

use App\Models\Transfer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A multi-file transfer writes one row per file, which made the admin history
 * read as a wall of near-identical lines. These cover the grouping that turns
 * one WeTransfer link back into one row.
 */
class AdminTransferHistoryTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function file(User $u, ?string $batch, string $name, int $size, string $when = '2026-08-18 10:00:00'): Transfer
    {
        return Transfer::create([
            'user_id' => $u->id,
            'batch_id' => $batch,
            'filename' => $name,
            'file_size' => $size,
            'google_drive_id' => 'd-' . $name,
            'transferred_at' => $when,
        ]);
    }

    public function test_a_multi_file_transfer_is_one_row_with_the_total_size(): void
    {
        $user = User::factory()->create();
        $this->file($user, 'batch-1', 'Shoot Day 1/a-roll.mov', 2_097_152);
        $this->file($user, 'batch-1', 'Shoot Day 1/callsheet.txt', 880);
        $this->file($user, 'batch-1', 'Shoot Day 1/Selects/hero.mov', 2_097_152);

        $html = $this->actingAs($this->admin())->get("/admin/users/{$user->id}")->assertOk()->getContent();

        // Summarised as one transfer, not three.
        $this->assertStringContainsString('3 files', $html);
        // 4195184 bytes = 4.0 MB, the sum rather than any single file.
        $this->assertStringContainsString('4 MB', $html);

        // And every file is still there to expand into.
        foreach (['a-roll.mov', 'callsheet.txt', 'hero.mov'] as $name) {
            $this->assertStringContainsString($name, $html);
        }
    }

    public function test_separate_transfers_stay_separate(): void
    {
        $user = User::factory()->create();
        $this->file($user, 'batch-1', 'one.mov', 1000, '2026-08-18 10:00:00');
        $this->file($user, 'batch-2', 'two.mov', 2000, '2026-08-18 11:00:00');

        $html = $this->actingAs($this->admin())->get("/admin/users/{$user->id}")->assertOk()->getContent();

        // Two single-file batches: neither should be summarised as a group.
        $this->assertStringNotContainsString('files</strong>', $html);
        $this->assertStringContainsString('one.mov', $html);
        $this->assertStringContainsString('two.mov', $html);
    }

    public function test_rows_from_before_batching_still_show(): void
    {
        // Every transfer recorded before batch_id existed has a null one, and
        // grouping on a null column would collapse them into a single row.
        $user = User::factory()->create();
        $this->file($user, null, 'old-one.mov', 1000, '2026-08-10 10:00:00');
        $this->file($user, null, 'old-two.mov', 2000, '2026-08-11 10:00:00');
        $this->file($user, null, 'old-three.mov', 3000, '2026-08-12 10:00:00');

        $html = $this->actingAs($this->admin())->get("/admin/users/{$user->id}")->assertOk()->getContent();

        foreach (['old-one.mov', 'old-two.mov', 'old-three.mov'] as $name) {
            $this->assertStringContainsString($name, $html);
        }
        $this->assertStringNotContainsString('files</strong>', $html);
    }

    public function test_one_users_batch_does_not_leak_into_another(): void
    {
        $mine = User::factory()->create();
        $theirs = User::factory()->create();
        $this->file($mine, 'batch-1', 'mine.mov', 1000);
        $this->file($theirs, 'batch-2', 'theirs.mov', 1000);

        $this->actingAs($this->admin())->get("/admin/users/{$mine->id}")->assertOk()
            ->assertSee('mine.mov')
            ->assertDontSee('theirs.mov');
    }

    public function test_the_newest_transfer_comes_first(): void
    {
        $user = User::factory()->create();
        $this->file($user, 'old', 'older.mov', 1000, '2026-08-01 10:00:00');
        $this->file($user, 'new', 'newer.mov', 1000, '2026-08-18 10:00:00');

        $html = $this->actingAs($this->admin())->get("/admin/users/{$user->id}")->assertOk()->getContent();

        $this->assertLessThan(
            strpos($html, 'older.mov'),
            strpos($html, 'newer.mov'),
            'the most recent transfer should be at the top'
        );
    }
}
