<?php

namespace Tests\Feature;

use App\Console\Commands\ExpireLapsedSubscriptions;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\UserSubscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Cancelling a subscription means "don't renew me" — it must never destroy the
 * period the customer already paid for. A real customer (#369) paid for Pro,
 * cancelled minutes later, and was immediately dropped to the free tier's 100MB
 * cap despite having paid through the following month.
 */
class CancelledSubscriptionAccessTest extends TestCase
{
    use RefreshDatabase;

    private function proPlan(): SubscriptionPlan
    {
        return SubscriptionPlan::create([
            'name' => 'Pro',
            'slug' => 'pro',
            'price_ngn' => 5000,
            'price_usd' => 10,
            'transfer_limit' => 100,
            'max_file_size' => 25 * 1024 * 1024 * 1024, // 25GB
            'features' => [],
            'is_active' => true,
            'sort_order' => 2,
        ]);
    }

    private function subscribedUser(SubscriptionPlan $plan, string $status, $expiresAt): User
    {
        $user = User::factory()->create(['subscription_tier' => 'pro']);

        $subscription = UserSubscription::create([
            'user_id' => $user->id,
            'subscription_plan_id' => $plan->id,
            'payment_provider' => 'polar',
            'status' => $status,
            'started_at' => now()->subDays(2),
            'expires_at' => $expiresAt,
            'transfers_used' => 0,
            'amount_paid' => $plan->price_usd,
            'currency' => 'USD',
        ]);

        $user->update(['active_subscription_id' => $subscription->id]);

        return $user->fresh();
    }

    public function test_a_cancelled_subscription_still_grants_access_until_the_paid_period_ends(): void
    {
        $plan = $this->proPlan();
        $user = $this->subscribedUser($plan, 'cancelled', now()->addMonth());

        $this->assertTrue(
            $user->hasActiveSubscription(),
            'a customer who cancelled but paid through next month still has access'
        );
        $this->assertSame('pro', $user->subscription_tier);
        $this->assertSame(
            25 * 1024 * 1024 * 1024,
            $user->activeSubscription->subscriptionPlan->max_file_size,
            'and still gets the Pro file-size cap they paid for'
        );
    }

    public function test_a_cancelled_subscription_stops_granting_access_once_the_period_lapses(): void
    {
        $plan = $this->proPlan();
        $user = $this->subscribedUser($plan, 'cancelled', now()->subDay());

        $this->assertFalse(
            $user->hasActiveSubscription(),
            'access ends when the paid period ends, not before and not after'
        );
    }

    public function test_the_expire_job_retires_lapsed_cancelled_subscriptions_and_downgrades_the_user(): void
    {
        $plan = $this->proPlan();
        $user = $this->subscribedUser($plan, 'cancelled', now()->subDay());
        $subscriptionId = $user->active_subscription_id;

        $this->artisan(ExpireLapsedSubscriptions::class)->assertSuccessful();

        $user->refresh();

        // Without this, honouring cancelled subs in isActive() would let a
        // cancelled user keep their tier forever.
        $this->assertSame('expired', UserSubscription::find($subscriptionId)->status);
        $this->assertSame('free', $user->subscription_tier);
        $this->assertNull($user->active_subscription_id);
    }

    public function test_the_expire_job_leaves_a_cancelled_subscription_alone_while_it_is_still_paid_for(): void
    {
        $plan = $this->proPlan();
        $user = $this->subscribedUser($plan, 'cancelled', now()->addMonth());
        $subscriptionId = $user->active_subscription_id;

        $this->artisan(ExpireLapsedSubscriptions::class)->assertSuccessful();

        $user->refresh();

        $this->assertSame('cancelled', UserSubscription::find($subscriptionId)->status);
        $this->assertSame('pro', $user->subscription_tier);
        $this->assertTrue($user->hasActiveSubscription());
    }
}
