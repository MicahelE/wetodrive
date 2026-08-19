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
 * Refunding in Polar only touches the order: the subscription carries on, so
 * without this the customer keeps their plan after getting their money back.
 * A Premium customer did exactly that, staying Premium for the seven weeks left
 * on his period while order.refunded arrived and went unhandled.
 *
 * Payload shapes here match a real refunded order pulled from the Polar API,
 * not a guess: status "refunded", refunded_amount == total_amount in minor
 * units, and subscription_id on the order.
 */
class PolarRefundTest extends TestCase
{
    use RefreshDatabase;

    private const OUR_PRODUCT = 'prod_wetodrive_pro';
    private const POLAR_SUB_ID = 'sub_polar_abc123';
    private const ORDER_ID = 'ord_abc123';

    protected function setUp(): void
    {
        parent::setUp();
        config(['polar.product_ids' => ['pro' => self::OUR_PRODUCT]]);
    }

    private function subscribedUser(string $status = 'active'): UserSubscription
    {
        $plan = SubscriptionPlan::create([
            'name' => 'Pro', 'slug' => 'pro', 'price_ngn' => 5000, 'price_usd' => 10,
            'transfer_limit' => 100, 'max_file_size' => 2 * 1024 * 1024 * 1024,
            'features' => [], 'is_active' => true, 'sort_order' => 1,
        ]);

        $user = User::factory()->create(['subscription_tier' => 'pro']);

        $sub = UserSubscription::create([
            'user_id' => $user->id,
            'subscription_plan_id' => $plan->id,
            'status' => $status,
            'payment_provider' => 'polar',
            'provider_subscription_id' => self::POLAR_SUB_ID,
            'amount_paid' => 10,
            'currency' => 'USD',
            'started_at' => now()->subMonth(),
            'expires_at' => now()->addMonth(),
        ]);

        $user->update(['active_subscription_id' => $sub->id]);

        return $sub;
    }

    private function refundEvent(array $overrides = []): array
    {
        return [
            'type' => 'order.refunded',
            'data' => array_merge([
                'id' => self::ORDER_ID,
                'status' => 'refunded',
                'product_id' => self::OUR_PRODUCT,
                'total_amount' => 7000,
                'refunded_amount' => 7000,
                'subscription_id' => self::POLAR_SUB_ID,
            ], $overrides),
        ];
    }

    public function test_a_full_refund_revokes_the_subscription(): void
    {
        $sub = $this->subscribedUser();

        // revokeSubscription talks to Polar; the point under test is that a full
        // refund reaches it at all.
        $polar = $this->mock(PolarService::class)->makePartial();
        $polar->shouldReceive('revokeSubscription')
            ->once()
            ->withArgs(fn (UserSubscription $s) => $s->is($sub))
            ->andReturn(true);

        $this->assertTrue($polar->handleWebhook($this->refundEvent()));
    }

    public function test_a_partial_refund_leaves_the_plan_alone(): void
    {
        // A goodwill partial refund is not a cancellation.
        $this->subscribedUser();

        $polar = $this->mock(PolarService::class)->makePartial();
        $polar->shouldNotReceive('revokeSubscription');

        $this->assertTrue($polar->handleWebhook($this->refundEvent(['refunded_amount' => 2000])));
    }

    public function test_it_marks_the_transaction_refunded(): void
    {
        $sub = $this->subscribedUser();

        $txn = PaymentTransaction::create([
            'user_id' => $sub->user_id,
            'user_subscription_id' => $sub->id,
            'provider' => 'polar',
            'provider_reference' => 'polar_order_' . self::ORDER_ID,
            'type' => 'subscription',
            'amount' => 70,
            'currency' => 'USD',
            'status' => 'success',
        ]);

        $polar = $this->mock(PolarService::class)->makePartial();
        $polar->shouldReceive('revokeSubscription')->andReturn(true);

        $polar->handleWebhook($this->refundEvent());

        // Revenue figures must stop counting a refunded order.
        $this->assertSame('refunded', $txn->fresh()->status);
    }

    public function test_a_refund_for_another_apps_product_is_ignored(): void
    {
        // One Polar org serves several apps and the endpoint sees every event.
        $this->subscribedUser();

        $polar = $this->mock(PolarService::class)->makePartial();
        $polar->shouldNotReceive('revokeSubscription');

        $this->assertTrue($polar->handleWebhook($this->refundEvent(['product_id' => 'prod_some_other_app'])));
    }

    public function test_a_refund_on_an_already_finished_subscription_does_nothing(): void
    {
        // Revoking twice would 4xx against Polar.
        $this->subscribedUser('expired');

        $polar = $this->mock(PolarService::class)->makePartial();
        $polar->shouldNotReceive('revokeSubscription');

        $this->assertTrue($polar->handleWebhook($this->refundEvent()));
    }

    public function test_a_one_off_refund_with_no_subscription_is_ignored(): void
    {
        $this->subscribedUser();

        $polar = $this->mock(PolarService::class)->makePartial();
        $polar->shouldNotReceive('revokeSubscription');

        $this->assertTrue($polar->handleWebhook($this->refundEvent(['subscription_id' => null])));
    }
}
