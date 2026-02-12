<?php

namespace App\Console\Commands;

use App\Mail\SubscriptionExpiringSoonMail;
use App\Models\UserSubscription;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendExpiringSubscriptionNotifications extends Command
{
    protected $signature = 'subscriptions:notify-expiring {--days=3 : Number of days before expiration}';

    protected $description = 'Send email notifications for subscriptions expiring in N days';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $targetDate = now()->addDays($days)->startOfDay();

        $subscriptions = UserSubscription::with(['user', 'subscriptionPlan'])
            ->where('status', 'active')
            ->whereDate('expires_at', $targetDate->toDateString())
            ->get();

        $count = 0;

        foreach ($subscriptions as $subscription) {
            try {
                Mail::to($subscription->user)->send(
                    new SubscriptionExpiringSoonMail($subscription->user, $subscription)
                );
                $count++;
            } catch (\Exception $e) {
                Log::warning('Failed to send expiring subscription email', [
                    'subscription_id' => $subscription->id,
                    'user_id' => $subscription->user_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("Sent {$count} expiring subscription notification(s).");

        return self::SUCCESS;
    }
}
