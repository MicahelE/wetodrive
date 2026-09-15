<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    {{-- Do not "improve" this title. GSC has it at #1 for "wetodrive" (93 clicks,
         77% CTR) and #3.2 for "wetransfer to google drive" (40 clicks), and this
         page carries roughly 73% of all search traffic. The previous design is
         kept at resources/views/home-legacy.blade.php if this needs reverting. --}}
    <title>WetoDrive - WeTransfer to Google Drive</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- The previous homepage carried none of the following. Matches SeoController's pattern. --}}
    <meta name="description" content="Transfer files from WeTransfer to Google Drive instantly. WetoDrive saves WeTransfer files directly to your Google Drive with no downloading and uploading, and no storage used on your device.">
    <meta name="keywords" content="wetransfer to google drive, save wetransfer to google drive, wetransfer google drive, transfer files from wetransfer, wetransfer alternative, wetransfer download, save wetransfer files, wetransfer to drive">
    <link rel="canonical" href="{{ route('home') }}">

    <meta property="og:title" content="WetoDrive - WeTransfer to Google Drive">
    <meta property="og:description" content="Transfer files from WeTransfer to Google Drive instantly. No downloading and uploading, no storage used on your device.">
    <meta property="og:url" content="{{ route('home') }}">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="WetoDrive">
    <meta property="og:image" content="{{ asset('logo.svg') }}">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="WetoDrive - WeTransfer to Google Drive">
    <meta name="twitter:description" content="Transfer files from WeTransfer to Google Drive instantly. No downloading and uploading, no storage used on your device.">

    {{-- favicon.svg is the single source of truth for the mark: every other view
         already points at it, so the logo only has to change in one place.
         favicon.ico carries the same artwork because browsers request
         /favicon.ico whether or not it is linked. --}}
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="apple-touch-icon" href="{{ asset('favicon.svg') }}">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">
    <meta name="theme-color" content="#2A42F7">

    {{-- @@ escapes are required: Blade reads a bare @context / @type as a directive. --}}
    <script type="application/ld+json">
    {
        "@@context": "https://schema.org",
        "@@type": "SoftwareApplication",
        "name": "WetoDrive",
        "applicationCategory": "UtilitiesApplication",
        "operatingSystem": "Web",
        "url": "{{ route('home') }}",
        "description": "Transfer files from WeTransfer to Google Drive instantly, without downloading and uploading.",
        "offers": { "@@type": "Offer", "price": "0", "priceCurrency": "USD", "description": "Free plan available" }
    }
    </script>

    @include('partials.site-analytics')

    @vite(['resources/js/hero.js'])

    @include('partials.site-styles')
</head>
<body>

<a href="#transfer" class="skip">Skip to the transfer form</a>

@include('partials.site-nav')

{{-- ============ HERO ============ --}}
<header class="hero">
    <canvas id="heroCanvas" aria-hidden="true"></canvas>
    <div class="wrap">
        @auth
            {{-- A signed-in user came here to move a file, so the input is the hero.
                 It used to sit a full screen down behind the sales pitch. --}}
            <h1>Welcome back, {{ Str::of(Auth::user()->name)->explode(' ')->first() }}</h1>
            <p class="tagline">Transfer files from WeTransfer to Google Drive instantly. Paste the link below.</p>

            <div class="box" id="transfer">
                @if(session('success'))
                    <div class="alert alert-success">{!! session('success') !!}</div>
                @endif
                @if(session('error'))
                    <div class="alert alert-error">{!! session('error') !!}</div>
                @endif
                @if($errors->any())
                    <div class="alert alert-error">
                        @foreach($errors->all() as $error) <div>{{ $error }}</div> @endforeach
                    </div>
                @endif

                <div class="box-head">
                    <h2>Transfer WeTransfer files</h2>
                    <div class="meta">
                        {{ ucfirst(Auth::user()->subscription_tier) }} plan
                        @php
                            // max(0, ...) matters: free users past 5 transfers were shown
                            // a negative count. The paid path already clamps this way.
                            $left = Auth::user()->hasActiveSubscription()
                                ? Auth::user()->activeSubscription->getRemainingTransfers()
                                : max(0, 5 - Auth::user()->total_transfers);
                        @endphp
                        &middot; <span data-transfers-remaining>{{ $left === null ? 'Unlimited' : $left }}</span> left
                        &middot; <span data-total-transfers>{{ Auth::user()->total_transfers }}</span> done
                    </div>
                </div>

                @if(Auth::user()->hasTrialTransferAvailable() && $left !== 0)
                    {{-- Worded off the allowance, not "your first transfer": the flag is
                         independent of total_transfers, so it can be true for someone
                         who has already done dozens and the old copy contradicted itself. --}}
                    <div class="alert alert-success">You have a one-time 1GB allowance available on this transfer.</div>
                @endif

                {{-- Drive access is a tick-box on Google's consent screen and is
                     easily clicked past. Without it nothing can ever land, so say
                     so before they paste a link rather than after a long upload. --}}
                @if (Auth::check() && ! Auth::user()->hasDriveAccess())
                    <div class="alert alert-error">
                        <strong>Google Drive access is not granted yet.</strong>
                        Files cannot be delivered until it is.
                        <a href="{{ route('auth.google') }}" style="font-weight:600;">Reconnect</a>
                        and tick the box for Drive access on Google's screen.
                    </div>
                @elseif (session('drive_permission_missing'))
                    <div class="alert alert-error">{{ session('drive_permission_missing') }}</div>
                @endif

                @if ($left === 0)
                    <div class="alert alert-error">
                        You have used all your transfers on the Free plan.
                        <a href="{{ route('subscription.pricing') }}" style="font-weight:600;">See plans</a> to keep going.
                    </div>
                @endif

                {{-- ids below are the contract with partials/transfer-script --}}
                <div id="transferFormContainer">
                    <form id="transferForm" method="POST" action="{{ route('transfer') }}">
                        @csrf
                        <label for="wetransfer_url">WeTransfer URL</label>
                        <input type="url" id="wetransfer_url" name="wetransfer_url" required autofocus
                               value="{{ old('wetransfer_url') }}"
                               placeholder="https://wetransfer.com/downloads/... or https://we.tl/t-...">
                        <div class="hint" id="urlHint">Works with full wetransfer.com links and short we.tl links.</div>

                        <label for="destination_folder">Destination folder <span class="opt">optional</span></label>
                        <div class="folder-row">
                            <input type="text" id="destination_folder" name="destination_folder"
                                   value="{{ old('destination_folder') }}"
                                   placeholder="Clients/Acme"
                                   list="folderSuggestions" autocomplete="off">
                            @if(config('services.google.picker_key'))
                                {{-- Carries the id of a folder chosen in Drive. Picking is
                                     what grants this app access to a folder it did not
                                     create, so the id matters, not the name. --}}
                                <input type="hidden" id="destination_folder_id" name="destination_folder_id">
                                <button type="button" id="browseDrive" class="browse-btn">Browse Drive</button>
                            @endif
                        </div>
                        {{-- Only folders this app created, because the drive.file scope
                             cannot see anything else in the user's Drive. --}}
                        @if(!empty($recentFolders) && count($recentFolders))
                            <datalist id="folderSuggestions">
                                @foreach($recentFolders as $folder)
                                    <option value="{{ $folder }}"></option>
                                @endforeach
                            </datalist>
                            <div class="hint">
                                Recent:
                                @foreach($recentFolders as $folder)
                                    <button type="button" class="folder-chip" data-folder="{{ $folder }}">{{ $folder }}</button>
                                @endforeach
                            </div>
                        @else
                            <div class="hint">Leave empty and files go straight to your Drive. Name a folder to sort them, and use / for subfolders.</div>
                        @endif

                        <button type="submit" class="submit-button" id="transferButton">Transfer to Google Drive</button>
                    </form>

                    {{-- Someone with a link in hand is exactly who might want to send
                         it on, so the offer sits with the form rather than in a menu. --}}
                    <div class="share-cta">
                        Working with someone else on this?
                        <a href="{{ route('shares.index') }}">Send them the files</a>
                        and they land in their Drive, on your plan's limits.
                    </div>
                    @if(config('services.dropbox.client_id'))
                        <div class="share-cta" style="margin-top:12px;">
                            Prefer Dropbox?
                            <a href="{{ route('dropbox') }}">Send WeTransfer files to Dropbox</a> instead.
                        </div>
                    @endif
                </div>

                {{-- data-resume is set when this user already has a transfer running.
                     The script reads it on load and reattaches to the live stream. --}}
                <div id="progressContainer" style="display:none;" @if($activeTransfer) data-resume="{{ $activeTransfer }}" @endif>
                    <div style="text-align:center; margin-bottom:14px;">
                        <div style="font-weight:650;" id="progressStatus">Initializing transfer...</div>
                        <div style="color:var(--muted); font-size:.9rem;" id="progressFilename"></div>
                    </div>
                    <div class="bar-track">
                        <div id="progressBar"><span id="progressPercent">0%</span></div>
                    </div>
                    <div class="pgrid">
                        <div class="pcell"><b id="bytesTransferred">0 MB</b><span>Transferred</span></div>
                        <div class="pcell"><b id="totalSize">0 MB</b><span>Total size</span></div>
                    </div>
                    <div id="statusMessage" class="alert alert-success" style="text-align:center;">
                        Transfer in progress. You can close this tab, it keeps running and you can come back for the result.
                    </div>
                    <div id="completionMessage" style="display:none; margin-top:14px;"></div>
                </div>
            </div>
        @else
            {{-- The H1 has to carry the brand and the primary keyword. GSC shows
                 this page at #1 for "wetodrive" (93 clicks, 77% CTR) and #3 for
                 "wetransfer to google drive" (40 clicks); the live page's H1 is
                 literally "WetoDrive". An H1 without either was a real regression. --}}
            <h1>WetoDrive sends WeTransfer straight to Google Drive</h1>
            <p class="tagline">Transfer files from WeTransfer to Google Drive instantly. If you take delivery of rushes, stills and masters all week, WetoDrive moves them into your Drive without downloading and uploading, and without using storage on your device.</p>
            <a href="{{ route('auth.google') }}" class="btn btn-onblue btn-lg" data-cta="hero">
                <svg width="18" height="18" viewBox="0 0 24 24" aria-hidden="true">
                    <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                    <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                    <path fill="#FBBC04" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                    <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                </svg>
                Get Started with Google Drive
            </a>
            <div class="fineprint">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                We can add files to your Drive, and nothing else. Your first transfer is free.
            </div>
            {{-- The Dropbox page stays separate so this one keeps saying Google Drive;
                 this is just the way through to it. --}}
            @if(config('services.dropbox.client_id'))
                <div class="fineprint">
                    Use Dropbox instead?
                    <a href="{{ route('dropbox') }}" style="color:inherit; font-weight:600;">Save WeTransfer to Dropbox</a>
                </div>
            @endif

            {{-- An illustration of the transfer screen, not a record of one: the
                 filename and size are representative. Marked aria-hidden because
                 it repeats what the copy above already says. --}}
            <div class="demo" aria-hidden="true">
                <div class="demo-top">
                    <span class="demo-dot"></span>
                    <span class="demo-title">Transfer in progress</span>
                    <span class="demo-file">Premium plan</span>
                </div>
                <div class="demo-name">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <rect x="3" y="3" width="14" height="18" rx="2" fill="#4285F4"/>
                        <path d="M7 9h6M7 13h4" stroke="#fff" stroke-width="1.8" stroke-linecap="round"/>
                    </svg>
                    wedding_4k_masters.zip
                </div>
                <div class="demo-bar"><div class="demo-fill"></div></div>
                <div class="demo-foot">
                    <span>48.2 GB</span>
                    <span>streaming to Drive</span>
                </div>
                <div class="demo-status">
                    <span class="demo-streaming">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 2v6M12 16v6M2 12h6M16 12h6"/></svg>
                        Nothing stored on your device
                    </span>
                    <span class="demo-saved">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#1E7E38" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                        Saved to your Google Drive
                    </span>
                </div>
            </div>
        @endauth
    </div>
</header>

{{-- ============ AUDIENCE MARQUEE ============ --}}
{{-- The list is rendered twice: the animation slides the track by exactly 50%,
     so the loop is seamless. aria-hidden on the copy stops screen readers
     reading the whole thing twice. --}}
@php
    $audiences = [
        ['Videographers', 'M23 7l-7 5 7 5V7z M14 5H3a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h11a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2z', '#4285F4'],
        ['Motion designers', 'M12 2v20 M2 12h20', '#EA4335'],
        ['Photo studios', 'M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z', '#FBBC04'],
        ['Creative agencies', 'M3 21h18 M5 21V7l8-4v18 M19 21V11l-6-4', '#34A853'],
        ['Post-production', 'M9 18V5l12-2v13 M6 21a3 3 0 1 0 0-6 3 3 0 0 0 0 6z', '#7C4DFF'],
        ['VFX artists', 'M12 2l2.4 7.4H22l-6 4.6 2.3 7.4-6.3-4.6L5.7 21 8 14 2 9.4h7.6z', '#FBBC04'],
        ['Wedding filmmakers', 'M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1-1.1a5.5 5.5 0 1 0-7.8 7.8l8.8 8.8 8.8-8.8a5.5 5.5 0 0 0 0-7.8z', '#EA4335'],
        ['Architects', 'M3 21h18 M9 8h1 M9 12h1 M9 16h1 M14 8h1 M14 12h1 M14 16h1 M5 21V4a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v17', '#00ACC1'],
        ['Music producers', 'M9 18V5l12-2v13 M6 21a3 3 0 1 0 0-6 3 3 0 0 0 0 6z M18 19a3 3 0 1 0 0-6 3 3 0 0 0 0 6z', '#7C4DFF'],
        ['Broadcast teams', 'M4 11a9 9 0 0 1 9 9 M4 4a16 16 0 0 1 16 16 M5 19a1 1 0 1 0 0 2 1 1 0 0 0 0-2z', '#34A853'],
    ];
@endphp
<div class="marquee">
    <div class="marquee-track">
        @foreach (array_merge($audiences, $audiences) as $i => $a)
            <span class="marquee-item" @if($i >= count($audiences)) aria-hidden="true" @endif>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="{{ $a[2] }}" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="{{ $a[1] }}"/></svg>
                {{ $a[0] }}
            </span>
        @endforeach
    </div>
</div>

{{-- ============ THREE CARDS (copy is the live homepage's, word for word) ============ --}}
<div class="wrap">
    <div class="cards">
        <div class="card">
            <div class="ico ico-amber">
                {{-- Bolt striking through a moving file: amber for speed, blue for the file. --}}
                <svg width="30" height="30" viewBox="0 0 32 32" fill="none">
                    <rect x="4" y="6" width="17" height="21" rx="3" fill="#4285F4"/>
                    <rect x="4" y="6" width="17" height="21" rx="3" fill="url(#gA)" opacity=".55"/>
                    <path d="M8 13h8M8 17h6" stroke="#fff" stroke-width="2" stroke-linecap="round" opacity=".85"/>
                    <path d="M22 3 14 16h5l-2 12 9-14h-5l1-11z" fill="#FBBC04" stroke="#fff" stroke-width="1.6" stroke-linejoin="round"/>
                    <defs><linearGradient id="gA" x1="0" y1="0" x2="1" y2="1">
                        <stop offset="0%" stop-color="#7BA5FF"/><stop offset="100%" stop-color="#2A42F7"/>
                    </linearGradient></defs>
                </svg>
            </div>
            <h3>Instant Transfer</h3>
            <p>No more manual downloading and uploading. Transfer files directly from WeTransfer to Google Drive.</p>
        </div>
        <div class="card">
            <div class="ico ico-green">
                {{-- A drive with the bay empty and a green tick: nothing stored on your machine. --}}
                <svg width="30" height="30" viewBox="0 0 32 32" fill="none">
                    <rect x="3" y="7" width="26" height="18" rx="4" fill="#34A853"/>
                    <rect x="3" y="7" width="26" height="18" rx="4" fill="url(#gB)" opacity=".5"/>
                    <rect x="7" y="18" width="18" height="3.4" rx="1.7" fill="#fff" opacity=".9"/>
                    <circle cx="24" cy="12.5" r="2" fill="#FBBC04"/>
                    <path d="M9 12.5h9" stroke="#fff" stroke-width="2" stroke-linecap="round" opacity=".8"/>
                    <defs><linearGradient id="gB" x1="0" y1="0" x2="1" y2="1">
                        <stop offset="0%" stop-color="#7BE0A0"/><stop offset="100%" stop-color="#1E7E38"/>
                    </linearGradient></defs>
                </svg>
            </div>
            <h3>Save Storage</h3>
            <p>Files stream directly to your Google Drive without taking up space on your device.</p>
        </div>
        <div class="card">
            <div class="ico ico-blue">
                {{-- Shield in Drive's blue with a green tick locked into it. --}}
                <svg width="30" height="30" viewBox="0 0 32 32" fill="none">
                    <path d="M16 2.5 27 6.5v9c0 8-5.4 12.6-11 14.4C10.4 28.1 5 23.5 5 15.5v-9z" fill="#4285F4"/>
                    <path d="M16 2.5 27 6.5v9c0 8-5.4 12.6-11 14.4C10.4 28.1 5 23.5 5 15.5v-9z" fill="url(#gC)" opacity=".55"/>
                    <path d="M11 16.2l3.4 3.4L21.2 12" stroke="#fff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M16 2.5 27 6.5v9c0 8-5.4 12.6-11 14.4z" fill="#EA4335" opacity=".14"/>
                    <defs><linearGradient id="gC" x1="0" y1="0" x2="1" y2="1">
                        <stop offset="0%" stop-color="#8FB4FF"/><stop offset="100%" stop-color="#1A2D99"/>
                    </linearGradient></defs>
                </svg>
            </div>
            <h3>Fast &amp; Secure</h3>
            <p>Powered by Google's secure infrastructure with enterprise-grade encryption.</p>
        </div>
    </div>
</div>

{{-- ============ HOW IT WORKS ============ --}}
<section id="how" class="rise">
    <div class="wrap">
        <div class="head">
            <h2>Three steps, then forget about it</h2>
            <p>The point is that you stop babysitting a download bar.</p>
        </div>
        <div class="steps">
            <div class="step">
                <div class="step-n"></div>
                <h3>Connect your Drive</h3>
                <p>Sign in with Google once. We ask only for permission to add files, nothing else.</p>
            </div>
            <div class="step">
                <div class="step-n"></div>
                <h3>Paste the link</h3>
                <p>Any WeTransfer link works, both short we.tl links and full download URLs.</p>
            </div>
            <div class="step">
                <div class="step-n"></div>
                <h3>Close the tab</h3>
                <p>We stream it server to server and email you the moment it lands in your Drive.</p>
            </div>
        </div>
    </div>
</section>

{{-- ============ STATS (straight from the database) ============ --}}
@if ($stats['transfers'] > 0)
<section style="padding-top:0;">
    <div class="wrap stats">
        <div class="stat">
            <b>{{ number_format($stats['accounts']) }}</b>
            <span>Google accounts connected</span>
        </div>
        <div class="stat">
            <b>{{ number_format($stats['transfers']) }}</b>
            <span>Transfers delivered to Drive</span>
        </div>
        {{-- Guarded separately from the transfer count: a dev db can have rows whose
             sizes round to nothing, and "over 0 GB" is worse than saying nothing. --}}
        @if ($stats['bytes'] > 1073741824)
            <div class="stat">
                <b>over {{ number_format(floor($stats['bytes'] / 1073741824)) }} GB</b>
                <span>Moved from WeTransfer to Google Drive</span>
            </div>
        @endif
    </div>
</section>
@endif

{{-- ============ TESTIMONIALS ============ --}}
{{-- Real replies to the check-in email. First name and country only: these were
     private replies, not submitted public reviews. --}}
<section id="words" class="rise" style="padding-top:0;">
    <div class="wrap">
        <div class="head">
            <h2>What people write back</h2>
            <p>We email everyone once to ask how it went. These are some of the replies.</p>
        </div>
        <div class="quotes">
            @foreach ([
                ['q' => 'It was perfect, thank you for building this.', 'i' => 'P', 'n' => 'Panos', 'c' => 'Greece'],
                ['q' => 'All was very nice and easy to understand. It has done what I wanted it to. Thank you.', 'i' => 'J', 'n' => 'Jawad', 'c' => 'Consultant, Lebanon'],
            ] as $t)
                <figure class="quote">
                    <blockquote>{{ $t['q'] }}</blockquote>
                    <figcaption>
                        <span class="avatar">{{ $t['i'] }}</span>
                        <span><b>{{ $t['n'] }}</b><small>{{ $t['c'] }}</small></span>
                    </figcaption>
                </figure>
            @endforeach
        </div>
    </div>
</section>

{{-- ============ WHY IT'S SAFE ============ --}}
<section id="why" class="rise">
    <div class="wrap perms">
        <div>
            <h2 style="margin-bottom:18px;">We ask for less than you'd expect</h2>
            <ul class="why" style="list-style:none;">
                <li>
                    <svg class="tick" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                    <div><b>We cannot read your Drive</b><span>The permission we request lets us add files. It does not let us browse, open, or delete what is already there.</span></div>
                </li>
                <li>
                    <svg class="tick" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                    <div><b>Your files are not kept</b><span>Files stream through and the temporary copy is deleted as soon as the upload finishes. We are a pipe, not a warehouse.</span></div>
                </li>
                <li>
                    <svg class="tick" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                    <div><b>Disconnect whenever you like</b><span>One button removes our access and wipes the stored token. You can also revoke it from your Google account page.</span></div>
                </li>
            </ul>
        </div>

        <div>
            <div style="font-size:.82rem; text-transform:uppercase; letter-spacing:.08em; color:var(--muted); margin-bottom:12px;">Permissions we request</div>
            <div class="perm-list">
                <div class="perm yes">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#2E9E52" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                    <div><b style="font-weight:650;">Add files to your Drive</b> <span style="color:var(--muted); font-size:.84rem;">drive.file scope</span></div>
                </div>
                @foreach (['Read your existing files', 'Delete or modify anything', 'See your Drive contents', 'Share anything on your behalf'] as $denied)
                    <div class="perm no">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
                        {{ $denied }}
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</section>

{{-- ============ FAQ ============ --}}
<section id="faq" class="rise">
    <div class="wrap">
        <div class="head"><h2>The things people ask first</h2></div>
        <div class="faq">
            @foreach ([
                ['Do I need a WeTransfer account?', 'No. You only need the link somebody sent you. We handle the rest.'],
                ['What happens if the transfer fails halfway?', 'Large files upload in chunks and resume automatically. If it still fails, it does not count against your quota and we email you what went wrong.'],
                ['How big a file can I move?', 'Your very first transfer stretches to 1GB. Pro handles up to 25GB, Premium goes to 500GB, and Ultra carries a full 1TB.'],
                ['Do you keep a copy of my files?', 'No. The file streams through to your Drive and the temporary copy is deleted the moment the upload completes.'],
                ['Can I cancel any time?', 'Yes, in one click from your dashboard. You keep everything you paid for until the end of the current period.'],
            ] as $i => $qa)
                <details class="qa" @if($i === 0) open @endif>
                    <summary>{{ $qa[0] }}</summary>
                    <p>{{ $qa[1] }}</p>
                </details>
            @endforeach
        </div>
    </div>
</section>

{{-- ============ CTA ============ --}}
<section style="padding-top:0;">
    <div class="wrap">
        <div class="cta">
            <h2>That link is going to expire</h2>
            <p>Move it into your Drive before it does. The first one is on us.</p>
            @auth
                <a href="#transfer" class="btn btn-primary btn-lg">Start a transfer</a>
            @else
                <a href="{{ route('auth.google') }}" class="btn btn-primary btn-lg" data-cta="footer-cta">Get Started with Google Drive</a>
            @endauth
        </div>
    </div>
</section>

@include('partials.site-footer')

<script>
    @include('partials.site-scripts')

    @include('partials.transfer-script')
</script>

</body>
</html>
