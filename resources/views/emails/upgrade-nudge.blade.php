<x-mail::message>
# Let's get that file across

Hi {{ $user->name }},

@if ($planName && $fileSize)
You tried to move a {{ $fileSize }} file recently, and your free plan wasn't big enough to carry it. That's an easy fix.

The **{{ $planName }} plan{{ $planPrice ? " ({$planPrice}/mo)" : '' }}** handles it comfortably, and you can start the same transfer again the moment you're on it.
@else
You tried to move a file recently that was bigger than the free plan allows. That's an easy fix.

**Pro handles files up to 25GB. Premium goes all the way to 500GB.** Whatever got blocked, one of these will carry it.
@endif

It works exactly the way you already tried: paste the WeTransfer link and it goes straight to your Google Drive. No downloading to your machine, no re-uploading, no waiting around.

<x-mail::button :url="route('subscription.pricing')">
See the plans
</x-mail::button>

Upgrade, paste your link again, and it goes through. If anything at all looks off, just reply to this email and it comes straight to me.

Thanks,<br>
{{ config('app.name') }}

<x-slot:subcopy>
Don't want emails like this? [Unsubscribe]({{ $unsubscribeUrl }}) and we won't send you any more. You'll still get essential messages about your transfers and payments.
</x-slot:subcopy>
</x-mail::message>
