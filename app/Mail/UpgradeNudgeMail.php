<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

/**
 * Nudge for a free user who tried to move a file bigger than their plan allows
 * and left without upgrading. Fires live at the moment of the bounce (see
 * TransferController::nudgeUserToUpgrade) and in batch for the backlog
 * (emails:upgrade-nudge).
 *
 * When we know the exact file that got blocked (the live path) we name it and
 * the plan that would have carried it; the batch path leaves those null and the
 * copy stays general. Marketing, so it carries an unsubscribe link.
 */
class UpgradeNudgeMail extends Mailable
{
    use SerializesModels;

    public function __construct(
        public User $user,
        public ?string $fileSize = null,
        public ?string $planName = null,
        public ?string $planPrice = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your file was bigger than the free plan allows');
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.upgrade-nudge',
            with: [
                'unsubscribeUrl' => URL::signedRoute('unsubscribe', ['user' => $this->user->id]),
            ],
        );
    }
}
