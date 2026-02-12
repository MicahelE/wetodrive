<x-mail::message>
# Your {{ $plan->name }} Plan is Active!

Hi {{ $user->name }},

Your subscription has been activated. Here's what you now have access to:

- **Transfers:** {{ $plan->isUnlimitedTransfers() ? 'Unlimited' : $plan->transfer_limit . ' per month' }}
- **Max file size:** {{ $plan->getFormattedFileSize() }}
- **Renews:** {{ $subscription->expires_at->format('M j, Y') }}

<x-mail::button :url="route('home')">
Start Transferring
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
