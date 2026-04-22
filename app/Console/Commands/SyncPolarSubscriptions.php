<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\PolarService;
use Illuminate\Console\Command;

class SyncPolarSubscriptions extends Command
{
    protected $signature = 'polar:sync {--user= : Sync a single user by ID}';

    protected $description = 'Reconcile subscriptions from Polar into user_subscriptions';

    public function handle(PolarService $polar): int
    {
        $userId = $this->option('user');

        $users = $userId
            ? User::where('id', $userId)->get()
            : User::whereNotNull('email_verified_at')->get();

        if ($users->isEmpty()) {
            $this->warn('No users to sync.');
            return self::SUCCESS;
        }

        $totalSynced = 0;

        foreach ($users as $user) {
            try {
                $synced = $polar->syncSubscriptionsFromPolar($user);
                $totalSynced += $synced;
                $this->line(sprintf('user=%d email=%s synced=%d', $user->id, $user->email, $synced));
            } catch (\Throwable $e) {
                $this->error(sprintf('user=%d email=%s error=%s', $user->id, $user->email, $e->getMessage()));
            }
        }

        $this->info(sprintf('Done. Total subscriptions synced: %d', $totalSynced));

        return self::SUCCESS;
    }
}
