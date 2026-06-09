<?php

namespace Tests\Feature;

use App\Models\PaymentTransaction;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\UserSubscription;
use App\Services\PolarService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PolarRenewalTest extends TestCase
{
    use RefreshDatabase;

    private const OUR_PRODUCT = 'prod_wetodrive_pro';
    private const POLAR_SUB_ID = 'sub_polar_abc123';

    protected function setUp(): void
    {
        parent::setUp();

        // Only our product counts as a WeToDrive product (shared Polar org).
        config(['polar.product_ids' => ['pro' => self::OUR_PRODUCT, 'premium' => 'prod_other_premium']]);
    }

    private function proPlan(): SubscriptionPlan
    {
        return SubscriptionPlan::create([
            'name' => 'Pro',
            'slug' => 'pro',
            'price_ngn' => 5000,
            'price_usd' => 10,
            'transfer_limit' => 100,
            'max_file_size' => 2 * 1024 * 1024 * 1024,
            'features' => [],
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }

    private function updatedEvent(array $overrides = []): array
    {
        return [
            'type' => 'subscription.updated',
            'data' => array_merge([
                'id' => self::POLAR_SUB_ID,
                'status' => 'active',
                'product_id' => self::OUR_PRODUCT,
                'current_period_end' => now()->addMonth()->toIso8601String(),
                'customer' => ['external_id' => '1'],
            ], $overrides),
        ];
    }

    public function test_renewal_reactivates_an_expired_downgraded_subscription_and_relinks_user(): void
    {
        $plan = $this->proPlan();

        // User was downgraded by subscriptions:expire after the period lapsed,
        // before the renewal synced.
        $user = User::factory()->create([
            'subscription_tier' => 'free',
            'active_subscription_id' => null,
        ]);

        $sub = UserSubscription::create([
            'user_id' => $user->id,
            'subscription_plan_id' => $plan->id,
            'payment_provider' => 'polar',
            'provider_subscription_id' => self::POLAR_SUB_ID,
            'status' => 'expired',
            'started_at' => now()->subMonth(),
            'expires_at' => now()->subDay(),
            'transfers_used' => 7,
            'period_resets_at' => now()->subDay(),
            'amount_paid' => 10,
            'currency' => 'USD',
        ]);

        $event = $this->updatedEvent(['customer' => ['external_id' => (string) $user->id]]);

        app(PolarService::class)->handleWebhook($event);

        $sub->refresh();
        $user->refresh();

        $this->assertSame('active', $sub->status, 'subscription should be reactivated');
        $this->assertTrue($sub->expires_at->isFuture(), 'period should be extended into the future');
        $this->assertSame($sub->id, (int) $user->active_subscription_id, 'user should be re-linked to the sub');
        $this->assertSame('pro', $user->subscription_tier, 'user tier should be restored');
        $this->assertTrue($sub->isActive());
    }

    public function test_renewal_records_a_renewal_transaction_when_the_period_advances(): void
    {
        $plan = $this->proPlan();
        $user = User::factory()->create();

        $sub = UserSubscription::create([
            'user_id' => $user->id,
            'subscription_plan_id' => $plan->id,
            'payment_provider' => 'polar',
            'provider_subscription_id' => self::POLAR_SUB_ID,
            'status' => 'active',
            'started_at' => now()->subMonth(),
            'expires_at' => now()->addDays(2),
            'transfers_used' => 4,
            'period_resets_at' => now()->addDays(2),
            'amount_paid' => 10,
            'currency' => 'USD',
        ]);

        $event = $this->updatedEvent(['customer' => ['external_id' => (string) $user->id]]);

        $polar = app(PolarService::class);
        $polar->handleWebhook($event);

        $this->assertSame(1, PaymentTransaction::where('type', 'renewal')->count());
        $this->assertSame(0, $sub->fresh()->transfers_used, 'transfers reset on renewal');

        // A second identical event (same new period) must not double-record.
        $polar->handleWebhook($event);
        $this->assertSame(1, PaymentTransaction::where('type', 'renewal')->count(), 'renewal must not be recorded twice');
    }

    public function test_event_for_a_foreign_product_is_ignored(): void
    {
        $plan = $this->proPlan();
        $user = User::factory()->create([
            'subscription_tier' => 'pro',
            'active_subscription_id' => null,
        ]);

        $sub = UserSubscription::create([
            'user_id' => $user->id,
            'subscription_plan_id' => $plan->id,
            'payment_provider' => 'polar',
            'provider_subscription_id' => self::POLAR_SUB_ID,
            'status' => 'cancelled',
            'started_at' => now()->subMonth(),
            'expires_at' => now()->subDay(),
            'transfers_used' => 0,
            'period_resets_at' => now()->subDay(),
            'amount_paid' => 10,
            'currency' => 'USD',
        ]);

        // Foreign product, but it collides on our subscription id and carries no
        // WeToDrive metadata — the guard must reject it before any mutation.
        $event = $this->updatedEvent([
            'product_id' => 'prod_some_other_app',
            'metadata' => [],
        ]);

        app(PolarService::class)->handleWebhook($event);

        $sub->refresh();
        $this->assertSame('cancelled', $sub->status, 'foreign event must not touch our subscription');
        $this->assertTrue($sub->expires_at->isPast(), 'period must be unchanged');
    }
}
