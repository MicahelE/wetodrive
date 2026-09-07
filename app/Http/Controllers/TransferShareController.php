<?php

namespace App\Http\Controllers;

use App\Mail\TransferShareInvitationMail;
use App\Models\TransferShare;
use App\Services\StreamTransferService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Sharing a WeTransfer link with a collaborator, who pulls it into their own
 * Drive under the sharer's plan limits. The link is the credential and the email
 * is only one way to deliver it, so everything hangs off the token.
 */
class TransferShareController extends Controller
{
    /** The sharer's own shares, newest first. */
    public function index()
    {
        $user = Auth::user();

        return view('shares.index', [
            'shares' => $user->transferShares()->latest()->limit(25)->get(),
            'remaining' => $user->sharesRemaining(),
            'limit' => $user->shareLimit(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'wetransfer_url' => 'required|url|max:2048',
            'recipient_email' => 'nullable|email|max:255',
        ]);

        $user = Auth::user();

        if (! $user->canShare()) {
            return back()->with('error', $user->shareLimit() === 0
                ? 'Sharing is not included in your plan yet.'
                : "You have used all {$user->shareLimit()} of your shares for this period.");
        }

        // Read the manifest now so the recipient sees what they are being sent
        // rather than an opaque link, and so a dead link fails here rather than
        // after someone has been emailed about it.
        $meta = ['recipient_email' => $request->input('recipient_email')];

        try {
            $service = new StreamTransferService();
            $listing = $service->listItems($service->resolvePageUrl($request->input('wetransfer_url')));

            $meta['title'] = $listing['title'] ?? null;
            $meta['total_size'] = $listing['size'] ?: null;
            $meta['file_count'] = count($listing['items']) ?: null;
        } catch (\Throwable $e) {
            Log::warning('Share created without a manifest', ['error' => $e->getMessage()]);

            return back()->with('error', 'That WeTransfer link could not be read. It may have expired or be password protected.');
        }

        $share = TransferShare::mint($user, $request->input('wetransfer_url'), $meta);

        if ($share->recipient_email) {
            try {
                Mail::to($share->recipient_email)->send(new TransferShareInvitationMail($share));
            } catch (\Throwable $e) {
                // The link still works, so a failed send must not lose the share.
                Log::warning('Share invitation email failed', [
                    'share_id' => $share->id, 'error' => $e->getMessage(),
                ]);

                return redirect()->route('shares.index')
                    ->with('warning', 'Share created, but the email could not be sent. Copy the link and send it yourself.');
            }
        }

        return redirect()->route('shares.index')->with('success', $share->recipient_email
            ? "Invitation sent to {$share->recipient_email}."
            : 'Share link ready. Copy it and send it to your collaborator.');
    }

    public function destroy(TransferShare $share)
    {
        abort_unless($share->user_id === Auth::id(), 403);

        if ($share->isClaimed()) {
            return back()->with('error', 'That share has already been used, so it cannot be cancelled.');
        }

        $share->update(['revoked_at' => now()]);

        return back()->with('success', 'Share cancelled. The allowance is back.');
    }

    /**
     * What the recipient lands on. Public: the token is the credential, and the
     * page has to work before they have an account.
     */
    public function show(string $token)
    {
        $share = TransferShare::where('token', $token)->firstOrFail();

        return view('shares.claim', [
            'share' => $share,
            'reason' => $share->unclaimableReason(),
            'signedIn' => Auth::check(),
        ]);
    }
}
