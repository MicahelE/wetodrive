<?php

namespace App\Console\Commands;

use App\Mail\FeatureAnnouncementMail;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Announces per-file delivery and destination folders.
 *
 * Sent in batches on purpose, for two reasons: the per-file path is new, and
 * Resend has a daily quota that a large run will hit partway through. Small runs
 * keep both problems small.
 *
 *   php artisan users:announce-features --dry-run   # the next 50
 *   php artisan users:announce-features             # send them
 *   php artisan users:announce-features --limit=0   # everyone left, quota permitting
 *
 * feature_email_sent is what makes the second run the exact complement of the
 * first, and what stops a re-run emailing anyone twice.
 */
class SendFeatureAnnouncement extends Command
{
    protected $signature = 'users:announce-features
        {--limit=50 : How many to email this run, oldest accounts first. Use --limit=0 for everyone left.}
        {--dry-run : List the recipients without sending anything}';

    protected $description = 'Email users about per-file delivery and destination folders.';

    public function handle(): int
    {
        $query = $this->eligible()->orderBy('id');

        // Defaults to 50 a run. Resend's daily quota stopped a 242 batch at 199,
        // and a batch that dies halfway is worse than several that fit.
        if (($limit = (int) $this->option('limit')) > 0) {
            $query->limit($limit);
        }

        $users = $query->get();

        if ($users->isEmpty()) {
            $this->info('No one left to email.');
            return self::SUCCESS;
        }

        $remaining = $this->eligible()->count() - $users->count();

        $this->info(sprintf(
            '%s %d user(s). %d would remain for the next batch.',
            $this->option('dry-run') ? 'Would email' : 'Emailing',
            $users->count(),
            $remaining,
        ));

        $sent = 0;

        foreach ($users as $user) {
            $line = sprintf('#%-4d %-40s transfers=%d', $user->id, $user->email, $user->total_transfers);

            if ($this->option('dry-run')) {
                $this->line('  [dry-run] ' . $line);
                continue;
            }

            try {
                Mail::to($user)->send(new FeatureAnnouncementMail($user));

                // Marked per user, immediately after its own send, so a crash
                // halfway through cannot re-email the ones already done.
                $user->update(['feature_email_sent' => true]);

                $this->info('  sent → ' . $line);
                Log::info('Feature announcement sent', ['user_id' => $user->id]);
                $sent++;
            } catch (\Throwable $e) {
                $this->error("  failed → #{$user->id} {$user->email}: {$e->getMessage()}");
                Log::warning('Feature announcement failed', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if (!$this->option('dry-run')) {
            $this->info("Done. {$sent} sent, {$remaining} left for the next batch.");
        }

        return self::SUCCESS;
    }

    /** Marketing-eligible: not opted out, not already sent, and not us. */
    private function eligible(): Builder
    {
        return User::query()
            ->where('email_opt_out', false)
            ->where('feature_email_sent', false)
            ->where('role', '!=', 'admin');
    }
}
