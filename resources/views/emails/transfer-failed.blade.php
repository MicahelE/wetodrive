<x-mail::message>
# Transfer Failed

Hi {{ $user->name }},

Unfortunately, your transfer could not be completed.

- **File:** {{ $filename }}
- **Error:** {{ $errorMessage }}

You can try again by pasting the WeTransfer link on our site. If the link has expired, ask the sender for a new one.

<x-mail::button :url="route('home')">
Try Again
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
