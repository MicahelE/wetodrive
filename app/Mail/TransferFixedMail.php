<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

/**
 * Tells someone whose transfer failed on the recipient_id bug that it works
 * again. Sent once, by hand, via emails:transfer-fixed.
 *
 * Same copy for everyone, including the two who paid while it was broken.
 */
class TransferFixedMail extends Mailable
{
    use SerializesModels;

    public function __construct(public User $user) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your transfer issue has been fixed');
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.transfer-fixed',
            with: [
                'unsubscribeUrl' => URL::signedRoute('unsubscribe', ['user' => $this->user->id]),
            ],
        );
    }
}
