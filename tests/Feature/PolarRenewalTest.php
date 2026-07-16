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

    public function test_a_brand_new_subscription_does_not_record_a_phantom_renewal(): void
    {
        // The exact production scenario: on a new subscription the success-redirect
        // sync and the subscription.created webhook both run for the same Polar id.
        // They report the SAME period end in different string formats. The old
        // string comparison treated the second as a renewal; it must not.
        $plan = $this->proPlan();
        $user = User::factory()->create();

        $periodEndCreated = '2026-08-16T03:41:12+00:00'; // e.g. Polar ->format('c')
        $periodEndSynced = '2026-08-16T03:41:12Z';       // same instant, different string

        $created = [
            'type' => 'subscription.created',
            'data' => [
                'id' => self::POLAR_SUB_ID,
                'status' => 'active',
                'product_id' => self::OUR_PRODUCT,
                'current_period_end' => $periodEndCreated,
                'customer' => ['external_id' => (string) $user->id],
                'metadata' => ['user_id' => (string) $user->id, 'plan_id' => (string) $plan->id],
            ],
        ];

        $polar = app(PolarService::class);
        $polar->handleWebhook($created);

        // Second pass for the same subscription, same period, different format.
        $polar->handleWebhook($this->updatedEvent([
            'customer' => ['external_id' => (string) $user->id],
            'current_period_end' => $periodEndSynced,
        ]));

        $this->assertSame(
            0,
            PaymentTransaction::where('type', 'renewal')->count(),
            'creating a subscription must not look like a renewal'
        );
        $this->assertSame('active', UserSubscription::where('provider_subscription_id', self::POLAR_SUB_ID)->value('status'));
    }

    public function test_purge_command_deletes_creation_time_renewals_but_keeps_genuine_ones(): void
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
            'expires_at' => now()->addMonth(),
            'transfers_used' => 0,
            'amount_paid' => 10,
            'currency' => 'USD',
        ]);

        // Initial subscription charge at signup.
        $signup = PaymentTransaction::create([
            'user_id' => $user->id, 'user_subscription_id' => $sub->id, 'provider' => 'polar',
            'provider_reference' => 'wetodrive_polar_signup', 'type' => 'subscription',
            'status' => 'success', 'amount' => 10, 'currency' => 'USD',
        ]);
        // Phantom renewal, same second as the signup charge.
        $phantom = PaymentTransaction::create([
            'user_id' => $user->id, 'user_subscription_id' => $sub->id, 'provider' => 'polar',
            'provider_reference' => 'renewal_phantom', 'type' => 'renewal',
            'status' => 'success', 'amount' => 10, 'currency' => 'USD',
        ]);
        // Genuine renewal a month later (created_at isn't fillable, so set it directly).
        $genuine = PaymentTransaction::create([
            'user_id' => $user->id, 'user_subscription_id' => $sub->id, 'provider' => 'polar',
            'provider_reference' => 'renewal_real', 'type' => 'renewal',
            'status' => 'success', 'amount' => 10, 'currency' => 'USD',
        ]);
        \DB::table('payment_transactions')->where('id', $genuine->id)->update(['created_at' => now()->addMonth()]);

        $this->artisan('polar:purge-phantom-renewals')->assertSuccessful();

        $this->assertNull(PaymentTransaction::find($phantom->id), 'creation-time renewal removed');
        $this->assertNotNull(PaymentTransaction::find($genuine->id), 'genuine renewal kept');
        $this->assertNotNull(PaymentTransaction::find($signup->id), 'subscription charge kept');
    }

    public function test_purge_command_dry_run_deletes_nothing(): void
    {
        $plan = $this->proPlan();
        $user = User::factory()->create();
        $sub = UserSubscription::create([
            'user_id' => $user->id, 'subscription_plan_id' => $plan->id, 'payment_provider' => 'polar',
            'provider_subscription_id' => self::POLAR_SUB_ID, 'status' => 'active',
            'started_at' => now()->subMonth(), 'expires_at' => now()->addMonth(),
            'transfers_used' => 0, 'amount_paid' => 10, 'currency' => 'USD',
        ]);
        PaymentTransaction::create([
            'user_id' => $user->id, 'user_subscription_id' => $sub->id, 'provider' => 'polar',
            'provider_reference' => 'wetodrive_polar_signup', 'type' => 'subscription',
            'status' => 'success', 'amount' => 10, 'currency' => 'USD',
        ]);
        $phantom = PaymentTransaction::create([
            'user_id' => $user->id, 'user_subscription_id' => $sub->id, 'provider' => 'polar',
            'provider_reference' => 'renewal_phantom', 'type' => 'renewal',
            'status' => 'success', 'amount' => 10, 'currency' => 'USD',
        ]);

        $this->artisan('polar:purge-phantom-renewals --dry-run')->assertSuccessful();

        $this->assertNotNull(PaymentTransaction::find($phantom->id), 'dry run must not delete');
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
