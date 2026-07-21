<?php

namespace Tests\Feature;

use App\Models\PaymentTransaction;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\UserSubscription;
use App\Services\PolarService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Polar refuses to confirm a checkout for a customer who already has a live
 * subscription, so an existing subscriber can never buy their way onto a higher
 * plan — the checkout dies on Polar's hosted page with "you already have a plan".
 * This is the #382 bug: 7 Premium checkouts created, none ever completed.
 */
class PlanChangeTest extends TestCase
{
    use RefreshDatabase;

    private const PRO_PRODUCT = 'prod_wetodrive_pro';
    private const PREMIUM_PRODUCT = 'prod_wetodrive_premium';
    private const POLAR_SUB_ID = 'sub_polar_abc123';

    protected function setUp(): void
    {
        parent::setUp();

        config(['polar.product_ids' => [
            'pro' => self::PRO_PRODUCT,
            'premium' => self::PREMIUM_PRODUCT,
        ]]);
    }

    private function plans(): array
    {
        $pro = SubscriptionPlan::create([
            'name' => 'Pro', 'slug' => 'pro', 'price_ngn' => 5000, 'price_usd' => 10,
            'transfer_limit' => 100, 'max_file_size' => 25 * 1024 * 1024 * 1024,
            'features' => [], 'is_active' => true, 'sort_order' => 2,
        ]);
        $premium = SubscriptionPlan::create([
            'name' => 'Premium', 'slug' => 'premium', 'price_ngn' => 50000, 'price_usd' => 80,
            'transfer_limit' => null, 'max_file_size' => 500 * 1024 * 1024 * 1024,
            'features' => [], 'is_active' => true, 'sort_order' => 3,
        ]);

        return [$pro, $premium];
    }

    private function proSubscriber(SubscriptionPlan $pro): array
    {
        $user = User::factory()->create(['subscription_tier' => 'pro', 'country_code' => 'BR']);

        $sub = UserSubscription::create([
            'user_id' => $user->id,
            'subscription_plan_id' => $pro->id,
            'payment_provider' => 'polar',
            'provider_subscription_id' => self::POLAR_SUB_ID,
            'status' => 'active',
            'started_at' => now()->subDays(15),
            'expires_at' => now()->addDays(15),
            'transfers_used' => 0,
            'period_resets_at' => now()->addDays(15),
            'amount_paid' => 10,
            'currency' => 'USD',
            'metadata' => ['current_period_start' => now()->subDays(15)->toIso8601String()],
        ]);

        $user->update(['active_subscription_id' => $sub->id]);

        return [$user->fresh(), $sub];
    }

    public function test_a_product_change_webhook_moves_the_subscription_onto_the_new_plan(): void
    {
        // The load-bearing fix: applyPolarState never touched subscription_plan_id,
        // so the subscription.updated webhook that follows a plan switch would stamp
        // the user straight back to the plan they just upgraded away from.
        [$pro, $premium] = $this->plans();
        [$user, $sub] = $this->proSubscriber($pro);

        app(PolarService::class)->handleWebhook([
            'type' => 'subscription.updated',
            'data' => [
                'id' => self::POLAR_SUB_ID,
                'status' => 'active',
                'product_id' => self::PREMIUM_PRODUCT,
                'current_period_end' => $sub->expires_at->toIso8601String(),
                'customer' => ['external_id' => (string) $user->id],
            ],
        ]);

        $sub->refresh();
        $user->refresh();

        $this->assertSame($premium->id, $sub->subscription_plan_id, 'subscription should follow the product');
        $this->assertSame('premium', $user->subscription_tier, 'user tier should follow the plan');
        // The dashboard prints amount_paid as the monthly price and recordRenewal()
        // bills renewals from it, so a stale $10 would misreport an $80 customer.
        $this->assertSame('80.00', (string) $sub->amount_paid, 'price should follow the plan');
        $this->assertSame(
            0,
            PaymentTransaction::where('type', 'renewal')->count(),
            'a plan change is not a renewal'
        );
    }

    private function orderEvent(array $overrides = []): array
    {
        return [
            'type' => 'order.updated',
            'data' => array_merge([
                'id' => 'polar_order_xyz',
                'status' => 'paid',
                'paid' => true,
                'billing_reason' => 'subscription_update',
                'total_amount' => 7000, // cents
                'currency' => 'usd',
                'product_id' => self::PREMIUM_PRODUCT,
                'subscription_id' => self::POLAR_SUB_ID,
                'customer' => ['external_id' => null],
                // Real upgrade orders INHERIT the subscription's original checkout
                // metadata, so transaction_id points at the already-paid initial
                // charge. The handler must branch on billing_reason, not this.
                'metadata' => ['transaction_id' => '999', 'plan_id' => '2', 'user_id' => '382'],
            ], $overrides),
        ];
    }

    public function test_a_paid_upgrade_order_is_recorded_as_a_payment(): void
    {
        // Polar sends order.updated (never order.paid/created) and an upgrade
        // proration has no pending row, so this webhook is the ONLY record of the
        // real money charged. #382's $70 was silently lost before this.
        [$pro, $premium] = $this->plans();
        [$user, $sub] = $this->proSubscriber($pro);

        // The initial $10 charge, already success, is what the inherited
        // transaction_id in the upgrade order points at. It must be left alone.
        $initial = PaymentTransaction::create([
            'user_id' => $user->id, 'user_subscription_id' => $sub->id, 'provider' => 'polar',
            'provider_reference' => 'wetodrive_polar_initial', 'type' => 'subscription',
            'status' => 'success', 'amount' => 10, 'currency' => 'USD',
        ]);

        app(PolarService::class)->handleWebhook($this->orderEvent([
            'customer' => ['external_id' => (string) $user->id],
            'metadata' => ['transaction_id' => (string) $initial->id],
        ]));

        $txns = PaymentTransaction::where('type', 'upgrade')->get();
        $this->assertCount(1, $txns);
        $this->assertSame('70.00', (string) $txns->first()->amount, 'cents converted to dollars');
        $this->assertSame('success', $txns->first()->status);
        $this->assertSame($user->id, (int) $txns->first()->user_id);
        $this->assertSame($sub->id, (int) $txns->first()->user_subscription_id);
        // The inherited transaction_id must not have been touched.
        $this->assertSame('success', $initial->fresh()->status);
        $this->assertSame('10.00', (string) $initial->fresh()->amount);
    }

    public function test_repeated_order_updated_events_record_the_charge_once(): void
    {
        [$pro] = $this->plans();
        [$user] = $this->proSubscriber($pro);
        $polar = app(PolarService::class);

        $event = $this->orderEvent(['customer' => ['external_id' => (string) $user->id]]);
        $polar->handleWebhook($event);
        $polar->handleWebhook($event);
        $polar->handleWebhook($event);

        $this->assertSame(1, PaymentTransaction::where('type', 'upgrade')->count(), 'idempotent on order id');
    }

    public function test_a_renewal_order_is_not_recorded_here_to_avoid_double_counting(): void
    {
        // Renewals are recorded by recordRenewal() from subscription.updated;
        // recording the subscription_cycle order too would count them twice.
        [$pro] = $this->plans();
        [$user] = $this->proSubscriber($pro);

        app(PolarService::class)->handleWebhook($this->orderEvent([
            'billing_reason' => 'subscription_cycle',
            'customer' => ['external_id' => (string) $user->id],
        ]));

        $this->assertSame(0, PaymentTransaction::count(), 'renewal orders are left to recordRenewal');
    }

    public function test_an_unpaid_order_update_records_nothing(): void
    {
        [$pro] = $this->plans();
        [$user] = $this->proSubscriber($pro);

        app(PolarService::class)->handleWebhook($this->orderEvent([
            'status' => 'pending',
            'paid' => false,
            'customer' => ['external_id' => (string) $user->id],
        ]));

        $this->assertSame(0, PaymentTransaction::count(), 'only paid orders are money');
    }

    public function test_an_initial_checkout_order_still_marks_its_pending_row_paid(): void
    {
        // The pre-existing path: our checkout stamps transaction_id and leaves a
        // pending row for the order webhook to flip to success.
        [$pro] = $this->plans();
        [$user, $sub] = $this->proSubscriber($pro);

        $pending = PaymentTransaction::create([
            'user_id' => $user->id, 'user_subscription_id' => $sub->id, 'provider' => 'polar',
            'provider_reference' => 'wetodrive_polar_signup', 'type' => 'subscription',
            'status' => 'pending', 'amount' => 10, 'currency' => 'USD',
        ]);

        app(PolarService::class)->handleWebhook($this->orderEvent([
            'billing_reason' => 'subscription_create',
            'total_amount' => 1000,
            'metadata' => ['transaction_id' => (string) $pending->id],
            'customer' => ['external_id' => (string) $user->id],
        ]));

        $this->assertSame('success', $pending->fresh()->status);
        $this->assertSame(0, PaymentTransaction::where('type', 'upgrade')->count(), 'no duplicate upgrade row');
    }

    public function test_an_existing_polar_subscriber_is_sent_to_confirm_instead_of_a_doomed_checkout(): void
    {
        [$pro, $premium] = $this->plans();
        [$user] = $this->proSubscriber($pro);

        $this->actingAs($user)
            ->post(route('subscription.subscribe'), ['plan_id' => $premium->id])
            ->assertRedirect(route('subscription.confirm-change', ['plan' => $premium->id]));

        // The old path burned a pending transaction per attempt; #382 had 7 of them.
        $this->assertSame(0, PaymentTransaction::count(), 'no doomed checkout should be created');
    }

    public function test_a_cancelled_but_unexpired_subscriber_still_gets_the_switch_path(): void
    {
        // #382's exact state: he cancelled mid-retry, which did not help because
        // Polar keeps the subscription live until the period ends.
        [$pro, $premium] = $this->plans();
        [$user, $sub] = $this->proSubscriber($pro);
        $sub->update(['status' => 'cancelled']);

        $this->actingAs($user)
            ->post(route('subscription.subscribe'), ['plan_id' => $premium->id])
            ->assertRedirect(route('subscription.confirm-change', ['plan' => $premium->id]));
    }

    public function test_a_freshly_cancelled_subscription_is_known_to_be_set_to_cancel(): void
    {
        // Regression: cancelSubscription() flips our status but does not refresh
        // metadata, so cancel_at_period_end is still false until a webhook lands.
        // Reading metadata alone made changePlan() skip the uncancel, which would
        // have upgraded the customer and still shut them off at period end.
        [$pro] = $this->plans();
        [, $sub] = $this->proSubscriber($pro);

        $sub->update([
            'status' => 'cancelled',
            'metadata' => ['cancel_at_period_end' => false],
        ]);

        $this->assertTrue($sub->isSetToCancel(), 'a cancelled sub is set to cancel even with stale metadata');

        $sub->update(['status' => 'active', 'metadata' => ['cancel_at_period_end' => true]]);
        $this->assertTrue($sub->isSetToCancel(), 'metadata alone is still enough');

        $sub->update(['status' => 'active', 'metadata' => []]);
        $this->assertFalse($sub->isSetToCancel(), 'a plain active sub is not set to cancel');
    }

    public function test_the_confirm_page_warns_a_cancelled_user_that_upgrading_restarts_the_subscription(): void
    {
        [$pro, $premium] = $this->plans();
        [$user, $sub] = $this->proSubscriber($pro);
        $sub->update(['status' => 'cancelled', 'metadata' => ['cancel_at_period_end' => false]]);

        $this->actingAs($user)
            ->get(route('subscription.confirm-change', ['plan' => $premium->id]))
            ->assertOk()
            ->assertSee('This restarts your subscription');
    }

    public function test_the_confirm_page_names_the_plan_and_the_estimate(): void
    {
        [$pro, $premium] = $this->plans();
        [$user] = $this->proSubscriber($pro);

        $this->actingAs($user)
            ->get(route('subscription.confirm-change', ['plan' => $premium->id]))
            ->assertOk()
            ->assertSee('Switch from Pro to Premium')
            ->assertSee('Confirm upgrade')
            ->assertSee('About $35.00 today'); // half a 30-day period left on a $70 difference
    }

    public function test_confirming_a_plan_you_are_already_on_goes_back_to_pricing(): void
    {
        [$pro] = $this->plans();
        [$user] = $this->proSubscriber($pro);

        $this->actingAs($user)
            ->get(route('subscription.confirm-change', ['plan' => $pro->id]))
            ->assertRedirect(route('subscription.pricing'));
    }

    public function test_proration_estimate_scales_with_the_time_left(): void
    {
        [$pro, $premium] = $this->plans();
        [, $sub] = $this->proSubscriber($pro);
        $polar = app(PolarService::class);

        $this->assertEqualsWithDelta(35.0, $polar->estimateProrationCharge($sub, $premium), 0.5, 'half a period left');

        $sub->update(['expires_at' => now()->subDay()]);
        $this->assertSame(0.0, $polar->estimateProrationCharge($sub, $premium), 'elapsed period charges nothing');

        // A downgrade must never produce a negative charge.
        $sub->update(['expires_at' => now()->addDays(15)]);
        $sub->setRelation('subscriptionPlan', $premium);
        $this->assertSame(0.0, $polar->estimateProrationCharge($sub, $pro));
    }

    public function test_a_free_user_still_goes_down_the_normal_checkout_path(): void
    {
        [, $premium] = $this->plans();
        $user = User::factory()->create(['subscription_tier' => 'free', 'active_subscription_id' => null]);

        // No Polar subscription, so subscribe() must not redirect to the switch flow.
        // It will try a real checkout and fail (no API key in tests), which is fine —
        // all we assert is that it did not take the confirm-change branch.
        $response = $this->actingAs($user)->post(route('subscription.subscribe'), ['plan_id' => $premium->id]);

        $response->assertRedirect();
        $this->assertStringNotContainsString('change', $response->headers->get('Location'));
    }
}
