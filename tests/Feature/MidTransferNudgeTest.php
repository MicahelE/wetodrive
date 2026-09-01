<?php

namespace Tests\Feature;

use App\Http\Controllers\StreamProgressController;
use App\Http\Controllers\TransferController;
use App\Mail\UpgradeNudgeMail;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * A limit rejection must not mail someone whose transfer is still running. That
 * is how #518 came to buy Pro during a batch that delivered all 20 files.
 */
class MidTransferNudgeTest extends TestCase
{
    use RefreshDatabase;

    private function nudge(User $user, int $size): void
    {
        SubscriptionPlan::create([
            'name' => 'Pro', 'slug' => 'pro', 'price_ngn' => 5000, 'price_usd' => 10,
            'transfer_limit' => 100, 'max_file_size' => 25 * 1024 * 1024 * 1024,
            'features' => [], 'is_active' => true, 'sort_order' => 2,
        ]);

        $m = new \ReflectionMethod(TransferController::class, 'nudgeUserToUpgrade');
        $m->setAccessible(true);
        $m->invoke(new TransferController(), $user, [
            'filename' => 'rushes', 'size' => $size,
        ], 100 * 1024 * 1024);
    }

    private function startTransfer(User $user): void
    {
        Cache::put("active_transfer_{$user->id}", 'transfer_abc', 900);
        StreamProgressController::updateProgress('transfer_abc', 1, 100, 'rushes');

        $this->assertNotNull(StreamProgressController::activeTransferFor($user->id));
    }

    public function test_it_stays_quiet_while_a_transfer_is_running(): void
    {
        Mail::fake();
        $user = User::factory()->create(['subscription_tier' => 'free']);
        $this->startTransfer($user);

        $this->nudge($user, 2 * 1024 * 1024 * 1024);

        Mail::assertNothingSent();
    }

    public function test_it_still_nudges_when_nothing_is_running(): void
    {
        Mail::fake();
        $user = User::factory()->create(['subscription_tier' => 'free']);

        $this->nudge($user, 2 * 1024 * 1024 * 1024);

        Mail::assertSent(UpgradeNudgeMail::class, fn ($m) => $m->hasTo($user->email));
    }
}
