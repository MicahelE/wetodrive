<x-mail::message>
Hi {{ $user->name }},

We discovered your transfer had a bug a while back. We have noted the error, and it has been addressed.

You can retry your transfer now. It should work well.

<x-mail::button :url="route('home')">
Retry your transfer
</x-mail::button>

Thanks for your patience.

{{ config('app.name') }}

<x-slot:subcopy>
[Unsubscribe]({{ $unsubscribeUrl }}) from emails like this. You will still get essential messages about your transfers and payments.
</x-slot:subcopy>
</x-mail::message>
