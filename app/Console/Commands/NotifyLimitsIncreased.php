<?php

namespace App\Console\Commands;

use App\Mail\LimitsIncreasedMail;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Tells users we previously turned away that the limits went up and their file
 * now fits. Used after the Pro 10GB -> 25GB / Premium 100GB -> 500GB raise.
 *
 *   php artisan users:notify-limits 369 --apologise --file="x.zip" --size=24.2GB --dry-run
 *   php artisan users:notify-limits 348 355 362 368 372 --dry-run
 */
class NotifyLimitsIncreased extends Command
{
    protected $signature = 'users:notify-limits
        {users* : User ids to email}
        {--apologise : Include an apology (for users who paid and were still blocked)}
        {--file= : The filename they were blocked on (single-user only)}
        {--size= : The formatted size of that file, e.g. 24.2GB (single-user only)}
        {--dry-run : List the recipients without sending anything}';

    protected $description = 'Email users whose blocked transfer now fits under the raised plan limits.';

    public function handle(): int
    {
        $ids = $this->argument('users');
        $dryRun = $this->option('dry-run');
        $apologise = $this->option('apologise');

        $users = User::whereIn('id', $ids)->get();

        if ($missing = array_diff($ids, $users->pluck('id')->map('strval')->all())) {
            $this->warn('No such user(s): ' . implode(', ', $missing));
        }

        if ($users->isEmpty()) {
            $this->error('No users to email.');
            return self::FAILURE;
        }

        if (($this->option('file') || $this->option('size')) && $users->count() > 1) {
            $this->error('--file and --size describe one specific transfer; pass a single user id.');
            return self::FAILURE;
        }

        $sent = 0;

        foreach ($users as $user) {
            $line = sprintf('#%d  %-38s  apologise=%s', $user->id, $user->email, $apologise ? 'yes' : 'no');

            if ($dryRun) {
                $this->line('  [dry-run] ' . $line);
                continue;
            }

            try {
                Mail::to($user)->send(new LimitsIncreasedMail(
                    $user,
                    $this->option('file'),
                    $this->option('size'),
                    $apologise,
                ));

                $this->info('  sent → ' . $line);
                Log::info('Limits-increased email sent', ['user_id' => $user->id, 'apologise' => $apologise]);
                $sent++;
            } catch (\Throwable $e) {
                $this->error("  failed → #{$user->id} {$user->email}: {$e->getMessage()}");
                Log::warning('Limits-increased email failed', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->newLine();
        $this->info($dryRun
            ? "Dry run — {$users->count()} recipient(s), nothing sent."
            : "Sent {$sent} of {$users->count()}.");

        return self::SUCCESS;
    }
}
