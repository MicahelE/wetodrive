<x-mail::message>
# Subscription Cancelled

Hi {{ $user->name }},

Your **{{ $planName }}** subscription has been cancelled.

@if($accessUntil)
You'll continue to have access to your plan features until **{{ $accessUntil->format('M j, Y') }}**.
@endif

After that, you'll be moved to the free plan with 5 transfers per month and a 100 MB file size limit.

You can resubscribe at any time to regain access to your plan features.

<x-mail::button :url="route('subscription.pricing')">
View Plans
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
