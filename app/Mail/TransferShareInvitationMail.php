<?php

namespace App\Mail;

use App\Models\TransferShare;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Tells a collaborator that someone is sending them a transfer. Transactional,
 * not marketing: it is the delivery of something the recipient was expecting, so
 * it carries no unsubscribe link. The sharer is named in the subject, because a
 * link arriving with no context reads like phishing.
 */
class TransferShareInvitationMail extends Mailable
{
    use SerializesModels;

    public function __construct(public TransferShare $share) {}

    public function envelope(): Envelope
    {
        $name = $this->share->sharer->name ?: 'Someone';

        return new Envelope(subject: "{$name} is sending you files on WeToDrive");
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.transfer-share-invitation',
            with: [
                'sharerName' => $this->share->sharer->name ?: 'A WeToDrive user',
                'claimUrl' => route('shares.show', $this->share->token),
                'title' => $this->share->title,
                'fileCount' => $this->share->file_count,
                'size' => $this->share->total_size ? $this->humanSize($this->share->total_size) : null,
                'expiresAt' => $this->share->expires_at,
            ],
        );
    }

    private function humanSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = $bytes > 0 ? (int) floor(log($bytes, 1024)) : 0;
        $i = min($i, count($units) - 1);

        return round($bytes / (1024 ** $i), $i > 2 ? 1 : 0) . $units[$i];
    }
}
