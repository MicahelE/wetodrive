<?php

namespace Tests\Feature;

use App\Console\Commands\SendUpgradeNudgeEmails;
use App\Mail\UpgradeNudgeMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * The backlog nudge mails real free customers, so the guards that matter are:
 * never mail someone twice, never mail someone who opted out, and never mail a
 * customer who has already upgraded.
 */
class UpgradeNudgeCommandTest extends TestCase
{
    use RefreshDatabase;

    private function nudge(array $ids, bool $dryRun = false)
    {
        $opts = ['--users' => implode(',', $ids)];
        if ($dryRun) {
            $opts['--dry-run'] = true;
        }
        return $this->artisan(SendUpgradeNudgeEmails::class, $opts)->assertSuccessful();
    }

    public function test_it_nudges_an_eligible_free_user(): void
    {
        Mail::fake();
        $user = User::factory()->create(['subscription_tier' => 'free']);

        $this->nudge([$user->id]);

        Mail::assertSent(UpgradeNudgeMail::class, fn ($m) => $m->hasTo($user->email));
    }

    public function test_it_skips_someone_who_already_upgraded(): void
    {
        Mail::fake();
        $user = User::factory()->create(['subscription_tier' => 'pro']);

        $this->nudge([$user->id]);

        Mail::assertNothingSent();
    }

    public function test_it_skips_opted_out_and_admins(): void
    {
        Mail::fake();
        $optedOut = User::factory()->create(['subscription_tier' => 'free', 'email_opt_out' => true]);
        $admin = User::factory()->create(['subscription_tier' => 'free', 'role' => 'admin']);

        $this->nudge([$optedOut->id, $admin->id]);

        Mail::assertNothingSent();
    }

    public function test_a_re_run_does_not_send_a_second_copy(): void
    {
        Mail::fake();
        $user = User::factory()->create(['subscription_tier' => 'free']);

        $this->nudge([$user->id]);
        $this->nudge([$user->id]);

        // The cooldown key is the whole point: a second run must be a no-op.
        Mail::assertSent(UpgradeNudgeMail::class, 1);
    }

    public function test_a_dry_run_sends_nothing_and_sets_no_cooldown(): void
    {
        Mail::fake();
        $user = User::factory()->create(['subscription_tier' => 'free']);

        $this->nudge([$user->id], dryRun: true);

        Mail::assertNothingSent();
        $this->assertFalse(Cache::has("upgrade-nudge:{$user->id}"));
    }
}
