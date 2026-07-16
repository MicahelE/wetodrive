<?php

namespace App\Console\Commands;

use App\Models\PaymentTransaction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Removes the phantom "renewal" transactions created by the old applyPolarState
 * string-comparison bug, where every brand-new subscription logged a renewal in
 * the same moment as its initial subscription charge (2x-inflating revenue).
 *
 * A phantom is a type='renewal' transaction created within a couple of minutes of
 * the same subscription's initial type='subscription' transaction — i.e. it marks
 * the creation, not a later billing cycle. Genuine renewals happen a full period
 * (weeks) later and are left untouched.
 *
 *   php artisan polar:purge-phantom-renewals --dry-run
 */
class PurgePhantomRenewals extends Command
{
    protected $signature = 'polar:purge-phantom-renewals
        {--window=120 : Seconds between the subscription and renewal txn to treat as phantom}
        {--dry-run : List the phantom rows without deleting anything}';

    protected $description = 'Delete phantom renewal transactions created at subscription-creation time.';

    public function handle(): int
    {
        $window = (int) $this->option('window');

        $renewals = PaymentTransaction::where('type', 'renewal')
            ->whereNotNull('user_subscription_id')
            ->orderBy('id')
            ->get();

        $phantoms = $renewals->filter(function (PaymentTransaction $renewal) use ($window) {
            // A sibling "subscription" charge for the same subscription, created
            // at essentially the same time, means this renewal marks the signup.
            $initial = PaymentTransaction::where('user_subscription_id', $renewal->user_subscription_id)
                ->where('type', 'subscription')
                ->orderBy('id')
                ->first();

            return $initial
                && abs($renewal->created_at->diffInSeconds($initial->created_at)) <= $window;
        });

        if ($phantoms->isEmpty()) {
            $this->info('No phantom renewal transactions found.');
            return self::SUCCESS;
        }

        $total = 0;
        foreach ($phantoms as $t) {
            $this->line(sprintf(
                '  %s txn#%-4d user#%-4d sub#%-4d %s%s  %s',
                $this->option('dry-run') ? '[dry-run]' : 'deleting ',
                $t->id,
                $t->user_id,
                $t->user_subscription_id,
                $t->amount,
                $t->currency,
                $t->created_at,
            ));
            $total += (float) $t->amount;
        }

        $this->newLine();
        $this->line(sprintf('  %d phantom renewal(s), %.2f in bogus revenue.', $phantoms->count(), $total));

        if ($this->option('dry-run')) {
            $this->info('Dry run — nothing deleted.');
            return self::SUCCESS;
        }

        $ids = $phantoms->pluck('id')->all();
        PaymentTransaction::whereIn('id', $ids)->delete();

        Log::info('Purged phantom renewal transactions', ['count' => count($ids), 'ids' => $ids]);
        $this->info('Deleted ' . count($ids) . ' phantom renewal transaction(s).');

        return self::SUCCESS;
    }
}
