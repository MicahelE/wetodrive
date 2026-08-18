<?php

namespace Tests\Feature;

use App\Mail\FeatureAnnouncementMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * The announcement goes out in batches because the per-file path is new. These
 * cover the two things that matter for that: nobody is emailed twice, and the
 * second batch is exactly the people the first one missed.
 */
class FeatureAnnouncementTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_limited_run_emails_only_that_many(): void
    {
        Mail::fake();
        User::factory()->count(10)->create();

        $this->artisan('users:announce-features', ['--limit' => 4])->assertSuccessful();

        Mail::assertSent(FeatureAnnouncementMail::class, 4);
        $this->assertSame(4, User::where('feature_email_sent', true)->count());
    }

    public function test_the_second_batch_is_exactly_the_remainder(): void
    {
        Mail::fake();
        $users = User::factory()->count(10)->create();

        $this->artisan('users:announce-features', ['--limit' => 4]);
        $firstBatch = User::where('feature_email_sent', true)->pluck('id');

        $this->artisan('users:announce-features', ['--limit' => 0]);

        Mail::assertSent(FeatureAnnouncementMail::class, 10);
        $this->assertSame(10, User::where('feature_email_sent', true)->count());
        // Everyone got it, and nobody twice.
        $this->assertCount(4, $firstBatch);
    }

    public function test_running_it_again_emails_nobody(): void
    {
        Mail::fake();
        User::factory()->count(3)->create();

        $this->artisan('users:announce-features', ['--limit' => 0]);
        Mail::fake(); // reset the recorder
        $this->artisan('users:announce-features', ['--limit' => 0]);

        Mail::assertNothingSent();
    }

    public function test_it_skips_opted_out_users_and_admins(): void
    {
        Mail::fake();
        User::factory()->create(['email_opt_out' => true]);
        User::factory()->create(['role' => 'admin']);
        $ok = User::factory()->create(['role' => 'user', 'email_opt_out' => false]);

        $this->artisan('users:announce-features', ['--limit' => 0]);

        Mail::assertSent(FeatureAnnouncementMail::class, 1);
        Mail::assertSent(FeatureAnnouncementMail::class, fn ($m) => $m->user->is($ok));
    }

    public function test_it_defaults_to_fifty_a_run(): void
    {
        // Resend's daily quota killed a 242 batch at 199, so a bare run is
        // deliberately small rather than "everyone".
        Mail::fake();
        User::factory()->count(60)->create();

        $this->artisan('users:announce-features')->assertSuccessful();

        Mail::assertSent(FeatureAnnouncementMail::class, 50);
        $this->assertSame(10, User::where('feature_email_sent', false)->where('role', '!=', 'admin')->count());
    }

    public function test_a_dry_run_sends_nothing_and_marks_nobody(): void
    {
        Mail::fake();
        User::factory()->count(5)->create();

        $this->artisan('users:announce-features', ['--dry-run' => true])->assertSuccessful();

        Mail::assertNothingSent();
        $this->assertSame(0, User::where('feature_email_sent', true)->count());
    }

    public function test_the_email_carries_an_unsubscribe_link(): void
    {
        // Marketing mail without a working opt-out is not something to send to 484 people.
        $user = User::factory()->create();
        $html = (new FeatureAnnouncementMail($user))->render();

        $this->assertStringContainsString('Unsubscribe', $html);
        $this->assertStringContainsString('/unsubscribe/' . $user->id, $html);
        $this->assertStringContainsString('signature=', $html);
    }
}
