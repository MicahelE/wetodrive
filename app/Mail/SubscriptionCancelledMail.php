<?php

namespace App\Mail;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SubscriptionCancelledMail extends Mailable
{
    use SerializesModels;

    public function __construct(
        public User $user,
        public string $planName,
        public ?Carbon $accessUntil,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your WeToDrive Subscription Has Been Cancelled');
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.subscription-cancelled');
    }
}
