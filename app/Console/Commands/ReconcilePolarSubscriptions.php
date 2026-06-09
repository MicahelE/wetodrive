<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\PolarService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ReconcilePolarSubscriptions extends Command
{
    protected $signature = 'polar:reconcile {--user= : Reconcile a single user id}';

    protected $description = 'Pull the latest Polar subscription state for Polar customers so missed/failed renewal webhooks self-heal (runs before subscriptions:expire)';

    public function handle(PolarService $polar): int
    {
        $query = User::query()
            ->whereHas('subscriptions', fn ($q) => $q->where('payment_provider', 'polar'));

        if ($userId = $this->option('user')) {
            $query->where('id', $userId);
        }

        $users = $query->get();
        $reconciled = 0;
        $failed = 0;

        foreach ($users as $user) {
            try {
                $polar->syncSubscriptionsFromPolar($user);
                $reconciled++;
            } catch (\Throwable $e) {
                $failed++;
                Log::warning('Polar reconcile failed for user', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("Reconciled {$reconciled} Polar customer(s), {$failed} failed.");

        return self::SUCCESS;
    }
}
