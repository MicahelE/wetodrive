<?php

namespace Tests\Feature;

use App\Http\Controllers\TransferController;
use Tests\TestCase;

/**
 * Many small files belong in one archive; few large files belong file-by-file.
 * Measured on production: ~5MB files move at 0.55 MB/s per-file against ~13 MB/s
 * as an archive, while files over 1GB beat the archive at ~20 MB/s.
 */
class ImportStrategyTest extends TestCase
{
    private function items(int $count, int $sizeEach): array
    {
        return array_map(
            fn ($i) => ['id' => "f{$i}", 'name' => "f{$i}.dat", 'size' => $sizeEach],
            range(1, $count)
        );
    }

    private function choose(array $items): array
    {
        return TransferController::chooseImportStrategy($items);
    }

    public function test_many_small_files_go_as_one_archive(): void
    {
        // Raed's transfer: 312 photos averaging 5.5MB.
        [$strategy] = $this->choose($this->items(312, (int) (5.5 * 1024 * 1024)));

        $this->assertSame('archive', $strategy);
    }

    public function test_a_few_large_files_go_file_by_file(): void
    {
        // Y Roth's transfer: 66 files averaging ~2GB.
        [$strategy] = $this->choose($this->items(66, 2 * 1024 * 1024 * 1024));

        $this->assertSame('per-file', $strategy);
    }

    public function test_the_file_cap_wins_even_for_large_files(): void
    {
        // Over the cap, per-file silently drops everything past file 168, so
        // size must not be able to argue its way back into that path.
        [$strategy, $why] = $this->choose($this->items(400, 5 * 1024 * 1024 * 1024));

        $this->assertSame('archive', $strategy);
        $this->assertStringContainsString('cap', $why);
    }

    public function test_the_cap_still_applies_while_the_ab_test_runs(): void
    {
        config(['transfer.ab_test' => true]);

        for ($i = 0; $i < 20; $i++) {
            [$strategy] = $this->choose($this->items(400, 5 * 1024 * 1024 * 1024));
            $this->assertSame('archive', $strategy, 'the cap is a safety rule, not an arm');
        }
    }

    public function test_the_ab_test_assigns_both_arms_and_labels_them(): void
    {
        config(['transfer.ab_test' => true]);

        $seen = [];
        for ($i = 0; $i < 60; $i++) {
            [$strategy, $why] = $this->choose($this->items(20, 8 * 1024 * 1024));
            $seen[$strategy] = true;
            $this->assertStringStartsWith('ab:', $why, 'the arm must be identifiable in the logs');
        }

        $this->assertArrayHasKey('archive', $seen);
        $this->assertArrayHasKey('per-file', $seen);
    }

    public function test_an_unlistable_transfer_falls_back_to_the_archive(): void
    {
        [$strategy] = $this->choose([]);

        $this->assertSame('archive', $strategy);
    }
}
