<x-mail::message>
# Your file fits now

Hi {{ $user->name }},

@if ($apologise)
I'm sorry. You subscribed to Pro and then found your file was still too large to transfer — that's a poor experience and it was our fault, not yours.

We've raised the limits. **Pro now handles files up to 25GB** (it was 10GB), and Premium goes up to 500GB.

Your Pro subscription is active and paid through {{ $user->activeSubscription?->expires_at?->format('F j, Y') }}, so there's nothing more to pay.
@else
We've raised our file size limits. **Pro now handles files up to 25GB** (it was 10GB), and Premium goes up to 500GB.

You tried a transfer recently that was over the limit — it will go through now.
@endif

@if ($filename)
- **Your file:** {{ $filename }}
@if ($fileSize)
- **Size:** {{ $fileSize }}
@endif
@endif

Just paste your WeTransfer link again and it will transfer straight to your Google Drive.

<x-mail::button :url="route('home')">
Start your transfer
</x-mail::button>

If anything goes wrong, reply to this email and it comes straight to me.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
