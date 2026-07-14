<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * One-click unsubscribe from marketing email. Reached via a signed URL embedded in
 * the email, so it works without logging in — a churned user won't sign in just to
 * opt out. The 'signed' middleware rejects tampered links, so nobody can opt out
 * somebody else by guessing a user id.
 *
 * This only suppresses marketing. Transactional mail (transfer complete, receipts)
 * still goes out.
 */
class UnsubscribeController extends Controller
{
    public function unsubscribe(User $user)
    {
        if (! $user->email_opt_out) {
            $user->update(['email_opt_out' => true]);

            Log::info('User unsubscribed from marketing email', [
                'user_id' => $user->id,
            ]);
        }

        return view('unsubscribed', ['user' => $user]);
    }
}
