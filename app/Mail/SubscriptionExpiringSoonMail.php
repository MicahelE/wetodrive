<?php

namespace App\Mail;

use App\Models\User;
use App\Models\UserSubscription;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SubscriptionExpiringSoonMail extends Mailable
{
    use SerializesModels;

    public function __construct(
        public User $user,
        public UserSubscription $subscription,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your WeToDrive Subscription Expires Soon');
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.subscription-expiring-soon');
    }
}
