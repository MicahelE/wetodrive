<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Admin alert: a user tried to transfer a file too big for ANY plan, so there is
 * no upgrade we can sell them. Sent to users with role=admin.
 */
class OversizeTransferAlertMail extends Mailable
{
    use SerializesModels;

    public function __construct(
        public User $customer,
        public string $filename,
        public string $fileSize,
        public string $topPlanLimit,
        public string $exceededBy,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Transfer above every plan: ' . $this->fileSize);
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.oversize-transfer-alert');
    }
}
