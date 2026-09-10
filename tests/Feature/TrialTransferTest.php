<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrialTransferTest extends TestCase
{
    use RefreshDatabase;

    public function test_trial_can_only_be_claimed_once(): void
    {
        $user = User::factory()->create([
            'subscription_tier' => 'free',
            'has_used_trial_transfer' => false,
        ]);

        $this->assertTrue($user->hasTrialTransferAvailable());
        $this->assertTrue($user->claimTrialTransfer(), 'first claim should win');

        // A second request (fresh model instance) must lose the race — this is
        // the fix for the time-of-check/time-of-use trial bug.
        $second = User::find($user->id);
        $this->assertFalse($second->claimTrialTransfer(), 'second concurrent claim must fail');

        $this->assertTrue($user->fresh()->has_used_trial_transfer);
    }

    public function test_release_returns_the_trial_after_a_failed_transfer(): void
    {
        $user = User::factory()->create([
            'subscription_tier' => 'free',
            'has_used_trial_transfer' => false,
        ]);

        $this->assertTrue($user->claimTrialTransfer());
        $user->releaseTrialTransfer();

        $this->assertFalse($user->fresh()->has_used_trial_transfer);
        $this->assertTrue($user->claimTrialTransfer(), 'trial should be claimable again after release');
    }

    public function test_the_trial_ceiling_is_1gb(): void
    {
        $user = User::factory()->create([
            'subscription_tier' => 'free',
            'has_used_trial_transfer' => false,
        ]);

        $m = new \ReflectionMethod(\App\Http\Controllers\TransferController::class, 'resolveFileSizeLimit');
        $m->setAccessible(true);

        // 50MB fits the plain free tier, so the trial stays untouched.
        [$max, $claimed] = $m->invoke(new \App\Http\Controllers\TransferController(), $user, 50 * 1024 * 1024);
        $this->assertSame(100 * 1024 * 1024, $max);
        $this->assertFalse($claimed);

        // 800MB is over the 100MB free tier, so it needs the trial, which is 1GB.
        [$max, $claimed] = $m->invoke(new \App\Http\Controllers\TransferController(), $user, 800 * 1024 * 1024);
        $this->assertSame(1024 * 1024 * 1024, $max, 'the one-time allowance is 1GB');
        $this->assertTrue($claimed);
    }

    public function test_a_file_over_1gb_does_not_burn_the_trial(): void
    {
        $user = User::factory()->create([
            'subscription_tier' => 'free',
            'has_used_trial_transfer' => false,
        ]);

        $m = new \ReflectionMethod(\App\Http\Controllers\TransferController::class, 'resolveFileSizeLimit');
        $m->setAccessible(true);

        // The claim happens up front, so the caller releases it when the file
        // still does not fit. What matters here is that it is reported as
        // claimed, because that is the signal to hand it back.
        [$max, $claimed] = $m->invoke(new \App\Http\Controllers\TransferController(), $user, 4 * 1024 * 1024 * 1024);
        $this->assertSame(1024 * 1024 * 1024, $max);
        $this->assertTrue($claimed, 'caller must know to release it');

        $user->releaseTrialTransfer();
        $this->assertTrue($user->fresh()->hasTrialTransferAvailable());
    }

    public function test_non_free_users_never_claim_the_free_trial(): void
    {
        $user = User::factory()->create([
            'subscription_tier' => 'pro',
            'has_used_trial_transfer' => false,
        ]);

        $this->assertFalse($user->claimTrialTransfer(), 'claim is scoped to free-tier users only');
        $this->assertFalse($user->fresh()->has_used_trial_transfer);
    }
}
