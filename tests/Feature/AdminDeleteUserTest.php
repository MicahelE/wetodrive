<?php

namespace Tests\Feature;

use App\Models\PaymentTransaction;
use App\Models\SubscriptionPlan;
use App\Models\Transfer;
use App\Models\User;
use App\Models\UserSubscription;
use App\Services\PolarService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class AdminDeleteUserTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function plan(): SubscriptionPlan
    {
        return SubscriptionPlan::create([
            'name' => 'Pro', 'slug' => 'pro', 'price_ngn' => 5000, 'price_usd' => 10,
            'transfer_limit' => 100, 'max_file_size' => 25 * 1024 * 1024 * 1024,
            'features' => [], 'is_active' => true, 'sort_order' => 2,
        ]);
    }

    public function test_admin_deletes_a_user_but_keeps_their_payment_history(): void
    {
        // No live Polar sub here, so the real service is fine (never called).
        $plan = $this->plan();
        $victim = User::factory()->create(['role' => 'user']);

        $sub = UserSubscription::create([
            'user_id' => $victim->id, 'subscription_plan_id' => $plan->id,
            'payment_provider' => 'paystack', 'status' => 'active',
            'started_at' => now(), 'expires_at' => now()->addMonth(),
            'transfers_used' => 0, 'amount_paid' => 10, 'currency' => 'USD',
        ]);
        $transfer = Transfer::create([
            'user_id' => $victim->id, 'file_size' => 1024, 'transferred_at' => now(),
        ]);
        $payment = PaymentTransaction::create([
            'user_id' => $victim->id, 'user_subscription_id' => $sub->id, 'provider' => 'paystack',
            'provider_reference' => 'ref_keepme', 'type' => 'subscription',
            'status' => 'success', 'amount' => 10, 'currency' => 'USD',
        ]);

        $this->actingAs($this->admin())
            ->delete(route('admin.users.destroy', $victim))
            ->assertRedirect(route('admin.users'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('users', ['id' => $victim->id]);
        $this->assertDatabaseMissing('transfers', ['id' => $transfer->id]);
        $this->assertDatabaseMissing('user_subscriptions', ['id' => $sub->id]);

        // Payment record survives, detached from the deleted user.
        $this->assertDatabaseHas('payment_transactions', ['id' => $payment->id, 'user_id' => null]);
    }

    public function test_deleting_a_user_revokes_their_live_polar_subscription(): void
    {
        $plan = $this->plan();
        $victim = User::factory()->create(['role' => 'user']);

        $sub = UserSubscription::create([
            'user_id' => $victim->id, 'subscription_plan_id' => $plan->id,
            'payment_provider' => 'polar', 'provider_subscription_id' => 'sub_polar_live',
            'status' => 'active', 'started_at' => now(), 'expires_at' => now()->addMonth(),
            'transfers_used' => 0, 'amount_paid' => 10, 'currency' => 'USD',
        ]);

        $mock = Mockery::mock(PolarService::class);
        $mock->shouldReceive('revokeSubscription')
            ->once()
            ->with(Mockery::on(fn ($s) => $s->id === $sub->id))
            ->andReturn(true);
        $this->app->instance(PolarService::class, $mock);

        $this->actingAs($this->admin())
            ->delete(route('admin.users.destroy', $victim))
            ->assertRedirect(route('admin.users'));

        $this->assertDatabaseMissing('users', ['id' => $victim->id]);
    }

    public function test_an_expired_polar_subscription_is_not_revoked(): void
    {
        $plan = $this->plan();
        $victim = User::factory()->create(['role' => 'user']);

        UserSubscription::create([
            'user_id' => $victim->id, 'subscription_plan_id' => $plan->id,
            'payment_provider' => 'polar', 'provider_subscription_id' => 'sub_polar_dead',
            'status' => 'expired', 'started_at' => now()->subMonths(2), 'expires_at' => now()->subMonth(),
            'transfers_used' => 0, 'amount_paid' => 10, 'currency' => 'USD',
        ]);

        $mock = Mockery::mock(PolarService::class);
        $mock->shouldNotReceive('revokeSubscription');
        $this->app->instance(PolarService::class, $mock);

        $this->actingAs($this->admin())
            ->delete(route('admin.users.destroy', $victim))
            ->assertRedirect(route('admin.users'));

        $this->assertDatabaseMissing('users', ['id' => $victim->id]);
    }

    public function test_an_admin_cannot_delete_their_own_account(): void
    {
        // This is the guard that keeps the last admin safe: you can't delete
        // yourself, and deleting anyone else always leaves you as an admin.
        $admin = $this->admin();

        $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $admin))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_an_admin_can_delete_a_fellow_admin(): void
    {
        // Deleting another admin is allowed (the actor remains an admin).
        $actor = $this->admin();
        $other = $this->admin();

        $this->actingAs($actor)
            ->delete(route('admin.users.destroy', $other))
            ->assertRedirect(route('admin.users'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('users', ['id' => $other->id]);
        $this->assertDatabaseHas('users', ['id' => $actor->id]);
    }

    public function test_a_non_admin_cannot_delete_users(): void
    {
        $victim = User::factory()->create(['role' => 'user']);
        $plainUser = User::factory()->create(['role' => 'user']);

        $this->actingAs($plainUser)
            ->delete(route('admin.users.destroy', $victim))
            ->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $victim->id]);
    }

    public function test_a_guest_cannot_delete_users(): void
    {
        $victim = User::factory()->create(['role' => 'user']);

        $this->delete(route('admin.users.destroy', $victim))
            ->assertRedirect(); // auth middleware bounces to login

        $this->assertDatabaseHas('users', ['id' => $victim->id]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
