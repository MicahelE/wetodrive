<!DOCTYPE html>
<html lang="en">
<head>
    <title>WetoDrive - Files sent to you</title>
    @include('shares._head')
</head>
<body>
<div class="wrap" style="max-width:560px">
    @if(session('error'))<div class="alert alert-error">{{ session('error') }}</div>@endif

    <div class="card">
        @if($reason)
            <h1>This link is no longer usable</h1>
            <p class="sub">{{ $reason }}</p>
            <a href="{{ route('home') }}" class="btn">Go to WeToDrive</a>
        @else
            <h1>{{ $share->sharer->name ?: 'Someone' }} is sending you files</h1>
            <p class="sub">Connect your Google Drive and these land there directly. Nothing to download, nothing to re-upload.</p>

            <ul class="facts">
                @if($share->title)
                    <li><span>Transfer</span><span>{{ $share->title }}</span></li>
                @endif
                @if($share->file_count)
                    <li><span>Files</span><span>{{ number_format($share->file_count) }}</span></li>
                @endif
                @if($share->total_size)
                    <li><span>Size</span><span>{{ round($share->total_size / 1073741824, 2) }} GB</span></li>
                @endif
                <li><span>Available until</span><span>{{ $share->expires_at->format('j F') }}</span></li>
            </ul>

            @if($signedIn)
                <form method="POST" action="{{ route('transfer') }}">
                    @csrf
                    <input type="hidden" name="share_token" value="{{ $share->token }}">
                    <input type="hidden" name="wetransfer_url" value="{{ $share->wetransfer_url }}">
                    <button type="submit" class="btn">Receive these files</button>
                </form>
                <p class="hint" style="margin-top:14px">
                    Going to {{ auth()->user()->email }}'s Drive. This does not use up any of your own transfers.
                </p>
            @else
                <a href="{{ route('auth.google', ['share' => $share->token]) }}" class="btn">Connect Google Drive to receive</a>
                <p class="hint" style="margin-top:14px">
                    You will need a Google account. Nothing reaches your Drive until you connect it.
                </p>
            @endif
        @endif
    </div>
</div>
</body>
</html>
