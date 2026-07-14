<?php

namespace Tests\Feature;

use App\Http\Controllers\TransferController;
use App\Mail\OversizeTransferAlertMail;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * When a file is too big for EVERY plan there is no upgrade to sell, so we tell
 * an admin instead. It must NOT fire on ordinary overages (those are the normal
 * upsell path) or a routine free-tier rejection would spam the inbox.
 */
class OversizeTransferAlertTest extends TestCase
{
    use RefreshDatabase;

    private function plans(): void
    {
        SubscriptionPlan::create([
            'name' => 'Free', 'slug' => 'free', 'price_ngn' => 0, 'price_usd' => 0,
            'transfer_limit' => 5, 'max_file_size' => 100 * 1024 * 1024,
            'features' => [], 'is_active' => true, 'sort_order' => 1,
        ]);
        SubscriptionPlan::create([
            'name' => 'Premium', 'slug' => 'premium', 'price_ngn' => 50000, 'price_usd' => 80,
            'transfer_limit' => null, 'max_file_size' => 500 * 1024 * 1024 * 1024, // 500GB top plan
            'features' => [], 'is_active' => true, 'sort_order' => 3,
        ]);
    }

    private function alert(User $user, int $size): void
    {
        $controller = new TransferController();

        $method = new \ReflectionMethod($controller, 'alertAdminsIfUnservable');
        $method->setAccessible(true);
        $method->invoke($controller, $user, [
            'filename' => 'raw-footage.zip',
            'size' => $size,
        ]);
    }

    public function test_it_alerts_admins_when_a_file_exceeds_every_plan(): void
    {
        Mail::fake();
        $this->plans();

        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['subscription_tier' => 'free']);

        $this->alert($user, 918 * 1024 * 1024 * 1024); // 918GB — over the 500GB top plan

        Mail::assertSent(OversizeTransferAlertMail::class, fn ($mail) => $mail->hasTo($admin->email));
    }

    public function test_it_stays_quiet_for_an_ordinary_overage_that_an_upgrade_would_fix(): void
    {
        Mail::fake();
        $this->plans();

        User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['subscription_tier' => 'free']);

        // 24GB busts the free tier but fits Premium — this is a sale, not an alert.
        $this->alert($user, 24 * 1024 * 1024 * 1024);

        Mail::assertNothingSent();
    }

    public function test_it_alerts_only_once_per_user_per_day(): void
    {
        Mail::fake();
        $this->plans();

        User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['subscription_tier' => 'free']);

        // A blocked user typically retries the same file several times.
        $this->alert($user, 918 * 1024 * 1024 * 1024);
        $this->alert($user, 918 * 1024 * 1024 * 1024);
        $this->alert($user, 918 * 1024 * 1024 * 1024);

        Mail::assertSent(OversizeTransferAlertMail::class, 1);
    }

    public function test_it_does_not_blow_up_when_there_are_no_admins(): void
    {
        Mail::fake();
        $this->plans();

        $user = User::factory()->create(['subscription_tier' => 'free']);

        $this->alert($user, 918 * 1024 * 1024 * 1024);

        Mail::assertNothingSent();
    }
}
