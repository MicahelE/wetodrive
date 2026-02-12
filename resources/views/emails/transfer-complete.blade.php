<x-mail::message>
# Transfer Complete

Hi {{ $user->name }},

Your file has been successfully transferred to Google Drive.

- **File:** {{ $filename }}
- **Size:** {{ $fileSize }}

<x-mail::button :url="$googleDriveUrl">
View in Google Drive
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
