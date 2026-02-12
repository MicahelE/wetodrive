<x-mail::message>
# Welcome to WeToDrive, {{ $user->name }}!

You're all set to transfer files from WeTransfer directly to your Google Drive.

Here's what you can do with your free plan:
- **5 transfers** per month
- Files up to **100 MB**

Ready to get started? Just paste a WeTransfer link and we'll handle the rest.

<x-mail::button :url="route('home')">
Start Transferring
</x-mail::button>

Need more transfers or larger files? Check out our Pro and Premium plans.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
