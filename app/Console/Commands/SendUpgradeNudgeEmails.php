<?php

namespace App\Console\Commands;

use App\Mail\UpgradeNudgeMail;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Backlog nudge for free users who hit the file-size wall before the live
 * in-controller nudge (TransferController::nudgeUserToUpgrade) existed. Those
 * bounces only live in the logs, so the audience is passed in by id:
 *
 *   php artisan emails:upgrade-nudge --users=462,465 --dry-run
 *   php artisan emails:upgrade-nudge --users=462,465
 *
 * The email is general (no specific file), so it needs no per-user file data.
 * Skips anyone opted out, an admin, or no longer on the free tier, and shares
 * the live path's 7-day cooldown key so a re-run can't double-send.
 */
class SendUpgradeNudgeEmails extends Command
{
    protected $signature = 'emails:upgrade-nudge
        {--users= : Comma-separated user ids to nudge (from the size-limit log lines)}
        {--dry-run : List the recipients without sending anything}';

    protected $description = 'Nudge free users who bounced off the file-size limit toward a paid plan.';

    public function handle(): int
    {
        $ids = collect(explode(',', (string) $this->option('users')))
            ->map(fn ($id) => (int) trim($id))
            ->filter()
            ->unique();

        if ($ids->isEmpty()) {
            $this->error('Pass --users=1,2,3 with the ids from the "File size exceeds user plan limit" log lines.');
            return self::FAILURE;
        }

        $users = User::query()
            ->whereIn('id', $ids)
            ->where('email_opt_out', false)
            ->where('role', '!=', 'admin')
            ->where('subscription_tier', 'free')
            ->orderBy('id')
            ->get();

        $skipped = $ids->reject(fn ($id) => $users->contains('id', $id));
        if ($skipped->isNotEmpty()) {
            $this->warn('Skipping (opted out, admin, upgraded, or unknown): ' . $skipped->implode(', '));
        }

        if ($users->isEmpty()) {
            $this->info('No eligible recipients.');
            return self::SUCCESS;
        }

        $sent = 0;
        foreach ($users as $user) {
            $line = sprintf('#%-4d %s', $user->id, $user->email);

            if ($this->option('dry-run')) {
                $this->line('  [dry-run] ' . $line);
                continue;
            }

            // Same cooldown the live nudge uses — don't stack a copy on someone
            // who just got nudged live, and make a re-run of this command a no-op.
            if (! Cache::add("upgrade-nudge:{$user->id}", true, now()->addDays(7))) {
                $this->line('  skipped (recently nudged) ' . $line);
                continue;
            }

            try {
                Mail::to($user)->send(new UpgradeNudgeMail($user));
                $this->info('  sent → ' . $line);
                Log::info('Upgrade nudge email sent', ['user_id' => $user->id]);
                $sent++;
            } catch (\Throwable $e) {
                Cache::forget("upgrade-nudge:{$user->id}"); // let a later run retry
                $this->error("  failed → {$line}: {$e->getMessage()}");
                Log::warning('Upgrade nudge email failed', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->newLine();
        $this->info($this->option('dry-run')
            ? "Dry run — {$users->count()} eligible recipient(s). Nothing sent."
            : "Sent {$sent} of {$users->count()}.");

        return self::SUCCESS;
    }
}
