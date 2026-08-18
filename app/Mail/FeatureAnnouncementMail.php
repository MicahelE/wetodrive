<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

/**
 * Announces the two changes that came out of a user request: files now arrive
 * individually rather than as one zip, and you can choose the destination folder.
 *
 * This is marketing, not transactional, so it carries an unsubscribe link.
 */
class FeatureAnnouncementMail extends Mailable
{
    use SerializesModels;

    public function __construct(public User $user) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your files now arrive ready to use');
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.feature-announcement',
            with: [
                'unsubscribeUrl' => URL::signedRoute('unsubscribe', ['user' => $this->user->id]),
            ],
        );
    }
}
