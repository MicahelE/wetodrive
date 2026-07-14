<?php

namespace App\Console\Commands;

use App\Models\UserSubscription;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ExpireLapsedSubscriptions extends Command
{
    protected $signature = 'subscriptions:expire';

    protected $description = 'Mark active subscriptions past their expiry as expired and downgrade those users to free';

    public function handle(): int
    {
        // 'cancelled' is included deliberately: a cancelled subscription keeps
        // access until its paid period ends (see UserSubscription::isActive), so
        // this job is the only thing that retires it. Omit it and those users
        // would keep their tier forever.
        $lapsed = UserSubscription::with('user')
            ->whereIn('status', ['active', 'cancelled'])
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->get();

        $count = 0;

        foreach ($lapsed as $subscription) {
            try {
                $subscription->expire();

                $user = $subscription->user;
                if ($user && (int) $user->active_subscription_id === (int) $subscription->id) {
                    $user->downgradeToFree();
                }

                $count++;
            } catch (\Exception $e) {
                Log::warning('Failed to expire lapsed subscription', [
                    'subscription_id' => $subscription->id,
                    'user_id' => $subscription->user_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("Expired {$count} lapsed subscription(s).");

        return self::SUCCESS;
    }
}
