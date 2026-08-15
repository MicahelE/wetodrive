<?php

namespace App\Console\Commands;

use App\Mail\TransferFixedMail;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

/**
 * One-off apology for the recipient_id bug (2026-08-02 to 2026-08-15), which
 * failed every transfer started from a WeTransfer notification email and
 * reported it to the user as an expired link.
 *
 * Recipients were identified from the production logs by pairing each failure
 * with the login that preceded it, so the ids are passed in explicitly rather
 * than derived from a query.
 */
class SendTransferFixedEmails extends Command
{
    protected $signature = 'emails:transfer-fixed
                            {--users= : Comma separated user ids}
                            {--dry-run : List recipients and send nothing}';

    protected $description = 'Tell users hit by the WeTransfer 403 bug that it is fixed';

    public function handle(): int
    {
        $ids = array_filter(explode(',', (string) $this->option('users')));

        if (! $ids) {
            $this->error('Pass --users=1,2,3. This is a one-off send, not a query.');

            return self::FAILURE;
        }

        $dry = (bool) $this->option('dry-run');
        $sent = 0;

        foreach (User::whereIn('id', $ids)->orderBy('id')->get() as $user) {
            if ($user->email_opt_out || $user->role === 'admin') {
                $this->line("  skip  {$user->id}  {$user->email}  (opted out or admin)");

                continue;
            }

            // Same guard the nudge uses: one apology per person, whatever
            // happens to this command afterwards.
            if (! $dry && ! Cache::add("transfer-fixed:{$user->id}", true, now()->addDays(30))) {
                $this->line("  skip  {$user->id}  {$user->email}  (already sent)");

                continue;
            }

            if ($dry) {
                $this->line("  would send  {$user->id}  {$user->email}");

                continue;
            }

            try {
                Mail::to($user)->send(new TransferFixedMail($user));
                $this->info("  sent  {$user->id}  {$user->email}");
                $sent++;
            } catch (\Throwable $e) {
                // Drop the marker so a rerun can retry this one.
                Cache::forget("transfer-fixed:{$user->id}");
                $this->error("  FAILED  {$user->id}  {$user->email}  {$e->getMessage()}");
            }
        }

        $this->newLine();
        $this->info($dry ? 'Dry run, nothing sent.' : "Sent {$sent}.");

        return self::SUCCESS;
    }
}
