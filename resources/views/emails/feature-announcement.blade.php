<x-mail::message>
# Two things worth knowing

Hi {{ $user->name }},

Until now, a WeTransfer with ten files in it landed in your Drive as one zip. Technically delivered, but you still had to download it, unzip it, and upload the pieces back. That defeated most of the point.

**Files now arrive individually.** Ten files means ten files in your Drive, each one openable, previewable and searchable straight away. If the sender organised them in folders, that structure comes across too.

**And you can choose where they land.** Type a folder name to create one, or hit Browse Drive and pick a folder you already have. Your last few are remembered, so the next transfer is one click.

Same as always otherwise: paste a link, and it goes straight to your Drive without downloading or re-uploading.

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
