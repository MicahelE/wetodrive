<x-mail::message>
# File Too Big For Every Plan

A user tried to transfer a file larger than the top plan allows, so there is no upgrade that would let them through.

- **User:** {{ $customer->name }} ({{ $customer->email }})
- **Current tier:** {{ $customer->subscription_tier ?? 'free' }}
- **File:** {{ $filename }}
- **Size:** {{ $fileSize }}
- **Top plan limit:** {{ $topPlanLimit }}
- **Over by:** {{ $exceededBy }}

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
