<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

/**
 * Win-back for people who bounced off the product for a reason that no longer
 * applies (the file size caps went up: Pro 10GB -> 25GB, Premium 100GB -> 500GB).
 *
 * Two audiences:
 *   'churned':   had a paid plan, no longer does.
 *   'abandoned': started a checkout and never paid.
 *
 * This is marketing, not transactional, so it carries an unsubscribe link.
 */
class WinBackMail extends Mailable
{
    use SerializesModels;

    public const CHURNED = 'churned';
    public const ABANDONED = 'abandoned';

    public function __construct(
        public User $user,
        public string $variant = self::CHURNED,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->variant === self::CHURNED
            ? 'We raised the limits: Pro now handles 25GB'
            : 'Since you last looked: Pro now handles 25GB');
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.win-back',
            with: [
                'unsubscribeUrl' => URL::signedRoute('unsubscribe', ['user' => $this->user->id]),
            ],
        );
    }
}
