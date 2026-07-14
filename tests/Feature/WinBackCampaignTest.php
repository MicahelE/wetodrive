<?php

namespace Tests\Feature;

use App\Console\Commands\SendWinBackEmails;
use App\Mail\WinBackMail;
use App\Models\PaymentTransaction;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\UserSubscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * Win-back is marketing sent to real customers, so the things that must not break
 * are: never mail someone twice, never mail someone who opted out, and never mail
 * a paying customer to say we miss them.
 */
class WinBackCampaignTest extends TestCase
{
    use RefreshDatabase;

    private function plan(): SubscriptionPlan
    {
        return SubscriptionPlan::create([
            'name' => 'Pro', 'slug' => 'pro', 'price_ngn' => 5000, 'price_usd' => 10,
            'transfer_limit' => 100, 'max_file_size' => 25 * 1024 * 1024 * 1024,
            'features' => [], 'is_active' => true, 'sort_order' => 2,
        ]);
    }

    /** Had a plan, it lapsed, they're back on free. */
    private function churnedUser(array $attrs = []): User
    {
        $user = User::factory()->create(array_merge(['subscription_tier' => 'free'], $attrs));

        UserSubscription::create([
            'user_id' => $user->id,
            'subscription_plan_id' => $this->plan()->id,
            'payment_provider' => 'polar',
            'status' => 'expired',
            'started_at' => now()->subMonths(2),
            'expires_at' => now()->subMonth(),
            'transfers_used' => 3,
            'amount_paid' => 10,
            'currency' => 'USD',
        ]);

        return $user->fresh();
    }

    private function transaction(User $user, string $status): void
    {
        PaymentTransaction::create([
            'user_id' => $user->id,
            'provider' => 'polar',
            'provider_reference' => 'ref_' . $user->id . '_' . $status,
            'type' => 'subscription',
            'status' => $status,
            'amount' => 10,
            'currency' => 'USD',
        ]);
    }

    public function test_it_emails_a_churned_subscriber(): void
    {
        Mail::fake();
        $user = $this->churnedUser();

        $this->artisan(SendWinBackEmails::class)->assertSuccessful();

        Mail::assertSent(WinBackMail::class, fn ($m) => $m->hasTo($user->email) && $m->variant === 'churned');
    }

    public function test_it_emails_someone_who_started_a_checkout_and_never_paid(): void
    {
        Mail::fake();
        $user = User::factory()->create(['subscription_tier' => 'free']);
        $this->transaction($user, 'pending');

        $this->artisan(SendWinBackEmails::class)->assertSuccessful();

        Mail::assertSent(WinBackMail::class, fn ($m) => $m->hasTo($user->email) && $m->variant === 'abandoned');
    }

    public function test_it_never_tells_a_paying_customer_we_miss_them(): void
    {
        Mail::fake();

        $user = User::factory()->create(['subscription_tier' => 'pro']);
        $subscription = UserSubscription::create([
            'user_id' => $user->id,
            'subscription_plan_id' => $this->plan()->id,
            'payment_provider' => 'polar',
            'status' => 'active',
            'started_at' => now()->subDays(3),
            'expires_at' => now()->addMonth(),
            'transfers_used' => 0,
            'amount_paid' => 10,
            'currency' => 'USD',
        ]);
        $user->update(['active_subscription_id' => $subscription->id]);
        $this->transaction($user, 'success');

        $this->artisan(SendWinBackEmails::class)->assertSuccessful();

        Mail::assertNothingSent();
    }

    public function test_someone_who_paid_is_not_treated_as_an_abandoned_checkout(): void
    {
        Mail::fake();

        // A pending row AND a success row — they eventually paid. Not abandoned.
        $user = User::factory()->create(['subscription_tier' => 'pro']);
        $this->transaction($user, 'pending');
        $this->transaction($user, 'success');

        $this->artisan(SendWinBackEmails::class)->assertSuccessful();

        Mail::assertNothingSent();
    }

    public function test_it_skips_anyone_who_has_opted_out(): void
    {
        Mail::fake();
        $this->churnedUser(['email_opt_out' => true]);

        $this->artisan(SendWinBackEmails::class)->assertSuccessful();

        Mail::assertNothingSent();
    }

    public function test_it_skips_admins(): void
    {
        Mail::fake();
        $this->churnedUser(['role' => 'admin']);

        $this->artisan(SendWinBackEmails::class)->assertSuccessful();

        Mail::assertNothingSent();
    }

    public function test_running_it_twice_sends_exactly_one_email(): void
    {
        Mail::fake();
        $user = $this->churnedUser();

        $this->artisan(SendWinBackEmails::class)->assertSuccessful();
        $this->artisan(SendWinBackEmails::class)->assertSuccessful();

        // The whole point of the sent-flag: a re-run must not mail a real customer again.
        Mail::assertSent(WinBackMail::class, 1);
        $this->assertTrue($user->fresh()->winback_email_sent);
    }

    public function test_a_dry_run_sends_nothing_and_leaves_the_flag_alone(): void
    {
        Mail::fake();
        $user = $this->churnedUser();

        $this->artisan(SendWinBackEmails::class, ['--dry-run' => true])->assertSuccessful();

        Mail::assertNothingSent();
        $this->assertFalse($user->fresh()->winback_email_sent);
    }

    public function test_the_signed_unsubscribe_link_opts_the_user_out(): void
    {
        $user = User::factory()->create();

        $this->get(URL::signedRoute('unsubscribe', ['user' => $user->id]))->assertOk();

        $this->assertTrue($user->fresh()->email_opt_out);
    }

    public function test_an_unsigned_unsubscribe_link_is_rejected(): void
    {
        $user = User::factory()->create();

        // Nobody gets to opt somebody else out by guessing an id.
        $this->get("/unsubscribe/{$user->id}")->assertForbidden();

        $this->assertFalse($user->fresh()->email_opt_out);
    }

    public function test_both_variants_render(): void
    {
        $user = $this->churnedUser();

        foreach ([WinBackMail::CHURNED, WinBackMail::ABANDONED] as $variant) {
            $html = (new WinBackMail($user, $variant))->render();

            $this->assertStringContainsString('25GB', $html);
            $this->assertStringContainsString('Unsubscribe', $html);
        }
    }
}
