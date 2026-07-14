<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Tells a user whose transfer we previously turned away that the plan limits have
 * gone up and their file now fits. $apologise adds an apology for users who had a
 * genuinely bad experience (paid, then still got blocked).
 */
class LimitsIncreasedMail extends Mailable
{
    use SerializesModels;

    public function __construct(
        public User $user,
        public ?string $filename = null,
        public ?string $fileSize = null,
        public bool $apologise = false,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->apologise
            ? 'Sorry, and good news: your transfer will go through now'
            : 'Good news: Pro now handles files up to 25GB');
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.limits-increased');
    }
}
