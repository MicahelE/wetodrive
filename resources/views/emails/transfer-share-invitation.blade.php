<x-mail::message>
# {{ $sharerName }} is sending you files

{{ $sharerName }} used WeToDrive to send you a transfer. Connect your Google Drive and it lands there directly, so there is nothing to download and re-upload.

@if($title)
- **Transfer:** {{ $title }}
@endif
@if($fileCount)
- **Files:** {{ number_format($fileCount) }}
@endif
@if($size)
- **Size:** {{ $size }}
@endif

<x-mail::button :url="$claimUrl">
Receive these files
</x-mail::button>

This link works once, and only until {{ $expiresAt->format('j F') }}. WeTransfer links expire after seven days, so it is worth doing now rather than later.

If you were not expecting this, you can ignore it. Nothing reaches your Drive unless you connect it yourself.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
