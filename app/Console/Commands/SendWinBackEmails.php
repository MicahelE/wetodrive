<?php

namespace App\Console\Commands;

use App\Mail\WinBackMail;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * One-off win-back after the file size limits went up (Pro 10GB -> 25GB,
 * Premium 100GB -> 500GB). Two audiences:
 *
 *   churned   — had a paid plan, now back on free.
 *   abandoned — started a checkout, never paid anything, ever.
 *
 * Marketing email, so it skips anyone who has opted out, and it sets
 * winback_email_sent immediately after each send so a re-run cannot deliver a
 * second copy to a real customer.
 *
 *   php artisan emails:win-back --dry-run
 *   php artisan emails:win-back --audience=churned
 */
class SendWinBackEmails extends Command
{
    protected $signature = 'emails:win-back
        {--audience=all : Which group to email — all, churned, or abandoned}
        {--dry-run : List the recipients without sending anything}';

    protected $description = 'Win-back email to churned subscribers and abandoned checkouts after the limit raise.';

    public function handle(): int
    {
        $audience = $this->option('audience');

        if (! in_array($audience, ['all', 'churned', 'abandoned'], true)) {
            $this->error("Unknown --audience '{$audience}'. Use all, churned, or abandoned.");
            return self::FAILURE;
        }

        $churned = $audience === 'abandoned' ? collect() : $this->churned();

        // A churned user may also have an abandoned checkout. They actually used
        // the product, so the churned message is the truer one — don't double up.
        $abandoned = $audience === 'churned'
            ? collect()
            : $this->abandoned()->reject(fn ($u) => $churned->contains('id', $u->id));

        if ($churned->isEmpty() && $abandoned->isEmpty()) {
            $this->info('No one to email — everyone is opted out, already sent, or does not qualify.');
            return self::SUCCESS;
        }

        $sent = 0;
        $sent += $this->deliver($churned, WinBackMail::CHURNED);
        $sent += $this->deliver($abandoned, WinBackMail::ABANDONED);

        $total = $churned->count() + $abandoned->count();

        $this->newLine();
        $this->info($this->option('dry-run')
            ? "Dry run — {$total} recipient(s) ({$churned->count()} churned, {$abandoned->count()} abandoned). Nothing sent."
            : "Sent {$sent} of {$total}.");

        return self::SUCCESS;
    }

    /** Had a paid plan, and is back on the free tier. */
    private function churned()
    {
        return $this->eligible()
            ->whereHas('subscriptions')
            ->where('subscription_tier', 'free')
            ->orderBy('id')
            ->get();
    }

    /** Started a checkout and has never had a successful payment. */
    private function abandoned()
    {
        return $this->eligible()
            ->whereHas('paymentTransactions', fn (Builder $q) => $q->where('status', 'pending'))
            ->whereDoesntHave('paymentTransactions', fn (Builder $q) => $q->where('status', 'success'))
            ->orderBy('id')
            ->get();
    }

    /** Marketing-eligible: not opted out, not already sent, and not us. */
    private function eligible(): Builder
    {
        return User::query()
            ->where('email_opt_out', false)
            ->where('winback_email_sent', false)
            ->where('role', '!=', 'admin');
    }

    private function deliver($users, string $variant): int
    {
        $sent = 0;

        foreach ($users as $user) {
            $line = sprintf('%-9s #%-4d %s', $variant, $user->id, $user->email);

            if ($this->option('dry-run')) {
                $this->line('  [dry-run] ' . $line);
                continue;
            }

            try {
                Mail::to($user)->send(new WinBackMail($user, $variant));

                // Flag immediately, exactly as SendCheckInEmails does — a re-run
                // must never send a real customer a second copy.
                $user->update(['winback_email_sent' => true]);

                $this->info('  sent → ' . $line);
                Log::info('Win-back email sent', ['user_id' => $user->id, 'variant' => $variant]);
                $sent++;
            } catch (\Throwable $e) {
                $this->error("  failed → {$line}: {$e->getMessage()}");
                Log::warning('Win-back email failed', [
                    'user_id' => $user->id,
                    'variant' => $variant,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $sent;
    }
}
