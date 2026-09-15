<x-mail::message>
# Transfer Complete

Hi {{ $user->name }},

Your file has been successfully transferred to {{ $destination }}.

- **File:** {{ $filename }}
- **Size:** {{ $fileSize }}

<x-mail::button :url="$googleDriveUrl">
View in {{ $destination }}
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
