<?php

namespace App\Console\Commands;

use App\Mail\CheckInMail;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendCheckInEmails extends Command
{
    protected $signature = 'emails:send-check-ins';

    protected $description = 'Send check-in emails to users who signed up 7+ days ago and have been inactive';

    public function handle(): int
    {
        $users = User::where('check_in_email_sent', false)
            ->where('created_at', '<=', now()->subDays(7))
            ->where(function ($query) {
                $query->whereNull('last_transfer_at')
                    ->orWhere('last_transfer_at', '<=', now()->subDays(7));
            })
            ->get();

        $count = 0;

        foreach ($users as $user) {
            try {
                Mail::to($user)->send(new CheckInMail($user));
                $user->update(['check_in_email_sent' => true]);
                $count++;

                Log::info('Check-in email sent', ['user_id' => $user->id, 'email' => $user->email]);
            } catch (\Exception $e) {
                Log::warning('Failed to send check-in email', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("Sent {$count} check-in email(s).");

        return self::SUCCESS;
    }
}
