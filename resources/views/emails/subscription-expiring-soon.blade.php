<x-mail::message>
# Your Subscription Expires Soon

Hi {{ $user->name }},

Your **{{ $subscription->subscriptionPlan->name }}** plan expires on **{{ $subscription->expires_at->format('M j, Y') }}**.

After expiration, you'll be moved to the free plan. Renew now to keep your current limits.

<x-mail::button :url="route('subscription.pricing')">
Renew Subscription
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
