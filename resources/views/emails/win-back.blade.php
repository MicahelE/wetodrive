<x-mail::message>
# We've raised the file size limits

Hi {{ $user->name }},

@if ($variant === 'churned')
You were on a paid plan with us a while back, and you're not any more. If the file size cap was part of why, that's worth a second look.

**Pro now handles files up to 25GB**, where it used to be 10GB. **Premium goes up to 500GB.**

Most people arrive with raw footage and photo shoots that our old limits simply couldn't take. That was our problem, not yours, and it's fixed.
@else
You started signing up for a paid plan a while back but didn't finish. No hard feelings. Here's what has changed since.

**Pro now handles files up to 25GB**, where it used to be 10GB. **Premium goes up to 500GB.**

If the limits were what gave you pause, they're a good deal roomier now.
@endif

Paste a WeTransfer link and it goes straight to your Google Drive. No downloading, no re-uploading.

<x-mail::button :url="route('home')">
Try a transfer
</x-mail::button>

If anything goes wrong, just reply to this email and it comes straight to me.

Thanks,<br>
{{ config('app.name') }}

<x-slot:subcopy>
Don't want emails like this? [Unsubscribe]({{ $unsubscribeUrl }}) and we won't send you any more. You'll still get essential messages about your transfers and payments.
</x-slot:subcopy>
</x-mail::message>
