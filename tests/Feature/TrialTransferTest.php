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
