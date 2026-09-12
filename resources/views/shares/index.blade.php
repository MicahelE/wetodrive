<!DOCTYPE html>
<html lang="en">
<head>
    <title>WetoDrive - Share a transfer</title>
    @include('shares._head')
</head>
<body>
<div class="wrap">
    <a href="{{ route('home') }}" class="back">&larr; Back to transfers</a>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('warning'))<div class="alert alert-warning">{{ session('warning') }}</div>@endif
    @if(session('error'))<div class="alert alert-error">{{ session('error') }}</div>@endif

    <div class="card">
        <h1>Share a transfer</h1>
        <p class="sub">Send a WeTransfer link to a collaborator. They connect their own Google Drive and the files land there, using your plan's limits rather than theirs.</p>

        <div class="allowance">
            @if($limit === 0)
                Sharing is not part of your plan yet. <a href="{{ route('subscription.pricing') }}">See plans</a>.
            @else
                <strong>{{ $remaining }} of {{ $limit }}</strong> share{{ $limit === 1 ? '' : 's' }} left.
                @if(!auth()->user()->hasActiveSubscription())
                    Free accounts get one, ever. <a href="{{ route('subscription.pricing') }}">Pro and Premium get more each month</a>.
                @endif
            @endif
        </div>

        @if($remaining > 0)
            <form method="POST" action="{{ route('shares.store') }}">
                @csrf
                <label for="wetransfer_url">WeTransfer link</label>
                <input type="url" id="wetransfer_url" name="wetransfer_url" required
                       placeholder="https://we.tl/t-XXXXXXXXXX"
                       value="{{ old('wetransfer_url', request('url')) }}">

                <label for="recipient_email">Their email <span style="font-weight:400;color:#6c757d">(optional)</span></label>
                <input type="email" id="recipient_email" name="recipient_email"
                       placeholder="colleague@example.com" value="{{ old('recipient_email') }}">
                <p class="hint">Leave this blank and you will get a link to send yourself, over WhatsApp or wherever suits.</p>

                <button type="submit" class="btn">Create share</button>
            </form>
        @endif
    </div>

    @if($shares->isNotEmpty())
        <div class="card">
            <h2>Your shares</h2>
            @foreach($shares as $share)
                <div class="share">
                    <div class="share-top">
                        <div>
                            <strong>{{ $share->title ?: 'WeTransfer files' }}</strong>
                            <div class="meta">
                                @if($share->file_count){{ number_format($share->file_count) }} files &middot; @endif
                                @if($share->total_size){{ round($share->total_size / 1073741824, 2) }} GB &middot; @endif
                                {{ $share->created_at->diffForHumans() }}
                                @if($share->recipient_email)<br>Sent to {{ $share->recipient_email }}@endif
                                @if($share->isClaimed())<br>Received by {{ $share->claimedBy->email ?? 'someone' }} {{ $share->claimed_at->diffForHumans() }}@endif
                            </div>
                        </div>
                        <div style="text-align:right">
                            @if($share->isClaimed())
                                <span class="pill pill-used">Received</span>
                            @elseif($share->isRevoked())
                                <span class="pill pill-dead">Cancelled</span>
                            @elseif($share->isExpired())
                                <span class="pill pill-dead">Expired</span>
                            @else
                                <span class="pill pill-live">Waiting</span>
                            @endif
                        </div>
                    </div>

                    @if($share->isClaimable())
                        <div class="copy">
                            <input type="text" readonly value="{{ route('shares.show', $share->token) }}"
                                   onclick="this.select()" aria-label="Share link">
                            <button type="button" class="btn copy-btn"
                                    data-link="{{ route('shares.show', $share->token) }}">Copy</button>
                        </div>
                        <form method="POST" action="{{ route('shares.destroy', $share) }}" style="margin-top:10px">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-quiet">Cancel this share</button>
                        </form>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</div>

<script>
document.querySelectorAll('.copy-btn').forEach(function (b) {
    b.addEventListener('click', function () {
        navigator.clipboard.writeText(b.dataset.link).then(function () {
            var was = b.textContent;
            b.textContent = 'Copied';
            setTimeout(function () { b.textContent = was; }, 1600);
        });
    });
});
</script>
</body>
</html>
