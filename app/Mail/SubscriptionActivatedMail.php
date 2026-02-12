<?php

namespace App\Mail;

use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\UserSubscription;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SubscriptionActivatedMail extends Mailable
{
    use SerializesModels;

    public function __construct(
        public User $user,
        public UserSubscription $subscription,
        public SubscriptionPlan $plan,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your ' . $this->plan->name . ' Plan is Active!');
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.subscription-activated');
    }
}
