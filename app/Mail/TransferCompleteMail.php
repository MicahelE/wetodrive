<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TransferCompleteMail extends Mailable
{
    use SerializesModels;

    public function __construct(
        public User $user,
        public string $filename,
        public string $fileSize,
        public string $googleDriveUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Transfer Complete — ' . $this->filename);
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.transfer-complete');
    }
}
