<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TransferFailedMail extends Mailable
{
    use SerializesModels;

    public function __construct(
        public User $user,
        public string $filename,
        public string $errorMessage,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Transfer Failed — ' . $this->filename);
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.transfer-failed');
    }
}
