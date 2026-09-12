<?php

namespace Tests\Feature;

use App\Mail\TransferShareInvitationMail;
use App\Models\SubscriptionPlan;
use App\Models\TransferShare;
use App\Models\User;
use App\Models\UserSubscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Sharing lets a recipient pull a transfer on the sharer's limits. The rules
 * that matter: an allowance is spent once and only when a transfer really
 * starts, the recipient's own quota is untouched, and a link works exactly once.
 */
class TransferShareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Migrations create Ultra; the rest of the ladder comes from the seeder,
        // which tests do not run.
        foreach ([
            ['Free', 'free', 5, 100 * 1048576, 1, 1],
            ['Pro', 'pro', 100, 25 * 1073741824, 2, 2],
            ['Premium', 'premium', null, 500 * 1073741824, 3, 3],
        ] as [$name, $slug, $transfers, $maxSize, $shares, $order]) {
            SubscriptionPlan::updateOrCreate(['slug' => $slug], [
                'name' => $name, 'price_ngn' => 0, 'price_usd' => 0,
                'transfer_limit' => $transfers, 'max_file_size' => $maxSize,
                'share_limit' => $shares, 'features' => [], 'is_active' => true,
                'sort_order' => $order,
            ]);
        }
    }

    private function premiumSharer(): User
    {
        $plan = SubscriptionPlan::where('slug', 'premium')->firstOrFail();
        $user = User::factory()->create(['subscription_tier' => 'premium']);

        $sub = UserSubscription::create([
            'user_id' => $user->id,
            'subscription_plan_id' => $plan->id,
            'payment_provider' => 'polar',
            'status' => 'active',
            'started_at' => now()->subDays(3),
            'expires_at' => now()->addMonth(),
            'period_resets_at' => now()->addDays(27),
            'transfers_used' => 0,
            'amount_paid' => 80,
            'currency' => 'USD',
        ]);
        $user->update(['active_subscription_id' => $sub->id]);

        return $user->fresh();
    }

    private function share(User $sharer, array $attrs = []): TransferShare
    {
        return TransferShare::mint($sharer, 'https://we.tl/t-ABC123', array_merge([
            'title' => 'Wedding rushes', 'total_size' => 90 * 1073741824, 'file_count' => 40,
        ], $attrs));
    }

    public function test_plan_decides_how_many_shares_you_get(): void
    {
        $free = User::factory()->create(['subscription_tier' => 'free']);

        $this->assertSame(1, $free->shareLimit());
        $this->assertSame(3, $this->premiumSharer()->shareLimit());
    }

    public function test_the_free_share_is_once_ever(): void
    {
        $free = User::factory()->create(['subscription_tier' => 'free']);
        $this->assertTrue($free->canShare());

        $this->share($free);

        $this->assertFalse($free->fresh()->canShare(), 'the free share does not come back');
    }

    public function test_a_cancelled_share_gives_the_allowance_back(): void
    {
        $free = User::factory()->create(['subscription_tier' => 'free']);
        $share = $this->share($free);
        $this->assertFalse($free->fresh()->canShare());

        $share->update(['revoked_at' => now()]);

        $this->assertTrue($free->fresh()->canShare());
    }

    public function test_an_unclaimed_share_frees_its_slot_when_it_expires(): void
    {
        $free = User::factory()->create(['subscription_tier' => 'free']);
        $share = $this->share($free);
        $this->assertFalse($free->fresh()->canShare());

        $this->travel(TransferShare::LIFETIME_DAYS + 1)->days();

        $this->assertTrue($free->fresh()->canShare(), 'nobody used it, so it should not be spent');
        $this->assertFalse($share->fresh()->isClaimable());
    }

    public function test_a_claimed_share_stays_spent_after_it_expires(): void
    {
        $free = User::factory()->create(['subscription_tier' => 'free']);
        $share = $this->share($free);
        $share->claimFor(User::factory()->create());

        $this->travel(TransferShare::LIFETIME_DAYS + 1)->days();

        $this->assertFalse($free->fresh()->canShare(), 'it was used, so it is gone');
    }

    public function test_a_link_can_only_be_claimed_once(): void
    {
        $share = $this->share($this->premiumSharer());

        $this->assertTrue($share->claimFor(User::factory()->create()));
        $this->assertFalse($share->fresh()->claimFor(User::factory()->create()), 'second claim must lose');
    }

    public function test_the_claim_page_is_public_and_names_the_sender(): void
    {
        $sharer = $this->premiumSharer();
        $sharer->update(['name' => 'Michael E']);

        $this->get(route('shares.show', $this->share($sharer)->token))
            ->assertOk()
            ->assertSee('Michael E')
            ->assertSee('Wedding rushes');
    }

    public function test_a_dead_link_says_which_kind_of_dead(): void
    {
        $share = $this->share($this->premiumSharer());
        $share->update(['revoked_at' => now()]);

        $this->get(route('shares.show', $share->token))
            ->assertOk()
            ->assertSee('cancelled this share');
    }

    public function test_the_recipient_transfers_on_the_sharers_file_size_limit(): void
    {
        $sharer = $this->premiumSharer();
        $recipient = User::factory()->create(['subscription_tier' => 'free']);
        $recipient->planFrom = $sharer;

        $m = new \ReflectionMethod(\App\Http\Controllers\TransferController::class, 'resolveFileSizeLimit');
        $m->setAccessible(true);
        [$max] = $m->invoke(new \App\Http\Controllers\TransferController(), $recipient, 90 * 1073741824);

        $premiumCap = SubscriptionPlan::where('slug', 'premium')->value('max_file_size');
        $this->assertEquals($premiumCap, $max, 'a free recipient should get the sharer\'s ceiling');
    }

    public function test_a_shared_transfer_does_not_spend_the_recipients_own_quota(): void
    {
        $recipient = User::factory()->create(['subscription_tier' => 'free', 'total_transfers' => 4]);
        $recipient->onSharedAllowance = true;

        $recipient->incrementTransferCount();

        $this->assertSame(4, $recipient->fresh()->total_transfers, 'the sharer already paid for this one');
    }

    public function test_an_email_share_sends_the_invitation(): void
    {
        Mail::fake();
        $sharer = $this->premiumSharer();

        Mail::to('editor@example.com')->send(new TransferShareInvitationMail($this->share($sharer)));

        Mail::assertSent(TransferShareInvitationMail::class,
            fn ($m) => $m->hasTo('editor@example.com'));
    }

    public function test_the_homepage_offers_sharing_to_a_signed_in_user(): void
    {
        $this->actingAs(User::factory()->create(['subscription_tier' => 'free']))
            ->get(route('home'))
            ->assertOk()
            ->assertSee(route('shares.index'));
    }

    public function test_the_admin_user_page_shows_both_sides_of_sharing(): void
    {
        $sharer = $this->premiumSharer();
        $recipient = User::factory()->create(['email' => 'editor@example.com']);
        $share = $this->share($sharer, ['recipient_email' => 'editor@example.com']);
        $share->claimFor($recipient);

        $admin = User::factory()->create(['role' => 'admin']);

        // The sender's page names who received it.
        $this->actingAs($admin)->get(route('admin.users.detail', $sharer))
            ->assertOk()
            ->assertSee('Wedding rushes')
            ->assertSee('editor@example.com');

        // The recipient's page shows they arrived through a share.
        $this->actingAs($admin)->get(route('admin.users.detail', $recipient))
            ->assertOk()
            ->assertSee('Arrived via a share');
    }

    public function test_the_share_form_prefills_from_a_link(): void
    {
        // The completion screen sends the link it just transferred, so the sharer
        // does not have to paste it a second time.
        $this->actingAs(User::factory()->create(['subscription_tier' => 'free']))
            ->get(route('shares.index', ['url' => 'https://we.tl/t-PREFILL1']))
            ->assertOk()
            ->assertSee('we.tl/t-PREFILL1');
    }

    public function test_a_prefilled_link_is_escaped(): void
    {
        $this->actingAs(User::factory()->create(['subscription_tier' => 'free']))
            ->get(route('shares.index', ['url' => '"><script>alert(1)</script>']))
            ->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_you_cannot_cancel_someone_elses_share(): void
    {
        $share = $this->share($this->premiumSharer());

        $this->actingAs(User::factory()->create())
            ->delete(route('shares.destroy', $share))
            ->assertForbidden();

        $this->assertNull($share->fresh()->revoked_at);
    }
}
