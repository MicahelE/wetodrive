@php
    // One list feeds both the visible FAQ and the FAQPage markup, so the two
    // can never say different things.
    $faqs = [
        ['Do I need a WeTransfer account?', 'No. You only need the link somebody sent you, either a short we.tl link or a full wetransfer.com download link.'],
        ['Do I need a Google account to save to Dropbox?', 'No. Continue with Dropbox and your WetoDrive account is set up from your Dropbox account, with no password to create. If you already use WetoDrive with Google under the same email address, and Dropbox has verified it, you land in that same account with the same plan.'],
        ['Where in my Dropbox do the files go?', 'Into the folder you type, such as Clients/Acme. Folders that do not exist yet are created for you. Leave the folder empty and the files go to the top level of your Dropbox.'],
        ['What if a file with the same name is already there?', 'Nothing is overwritten. Dropbox keeps both, and the new file gets a number added to its name, like "report (1).pdf".'],
        ['What happens if my Dropbox is full?', 'We check your free space before fetching anything. If the transfer will not fit, you are told straight away how much space it needs and how much you have, rather than finding out after a long download. Free Dropbox Basic accounts come with 2 GB.'],
        ['How big a file can I move?', 'Your WetoDrive plan sets the limit, the same as for Google Drive, and your Dropbox needs the free space for it. The pricing page lists what each plan allows.'],
        ['Can I close the tab while it runs?', 'Yes. The transfer runs on our servers, so you can close the tab or shut your laptop. We email you when the files are in your Dropbox, and this page shows the result if you come back.'],
        ['Do you keep a copy of my files?', 'No. Files over 1GB stream from WeTransfer to Dropbox without being stored on our servers. Smaller files pass through a temporary copy that is deleted as soon as the upload finishes.'],
        ['Does it work with expired or password-protected links?', 'No. WeTransfer links stop working once they expire, which is usually after 7 days, and password-protected transfers cannot be opened. Ask the sender for a fresh link.'],
        ['How do I disconnect Dropbox?', 'Use the Disconnect Dropbox button on this page. It revokes our access at Dropbox and deletes the stored token. You can also remove WetoDrive under Connected apps in your Dropbox settings.'],
    ];

    $schema = [
        [
            '@context' => 'https://schema.org',
            '@type' => 'SoftwareApplication',
            'name' => 'WetoDrive',
            'applicationCategory' => 'UtilitiesApplication',
            'operatingSystem' => 'Web',
            'url' => route('dropbox'),
            'description' => 'Save WeTransfer files straight to Dropbox, without downloading and uploading them again.',
            'offers' => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'USD', 'description' => 'Free plan available'],
        ],
        [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => array_map(fn ($qa) => [
                '@type' => 'Question',
                'name' => $qa[0],
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => $qa[1]],
            ], $faqs),
        ],
    ];

    $tick = '<svg class="tick" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>';
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WeTransfer to Dropbox - Save WeTransfer Files Straight to Dropbox | WetoDrive</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <meta name="description" content="Save WeTransfer files straight to your Dropbox. Paste a WeTransfer link and WetoDrive moves the files for you, with no downloading, no re-uploading and no space used on your device.">
    <meta name="keywords" content="wetransfer to dropbox, save wetransfer to dropbox, wetransfer dropbox, transfer wetransfer files to dropbox, move wetransfer to dropbox">
    <link rel="canonical" href="{{ route('dropbox') }}">

    <meta property="og:title" content="WeTransfer to Dropbox | WetoDrive">
    <meta property="og:description" content="Paste a WeTransfer link and the files land in your Dropbox. No downloading, no re-uploading.">
    <meta property="og:url" content="{{ route('dropbox') }}">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="WetoDrive">
    <meta property="og:image" content="{{ asset('logo.svg') }}">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="WeTransfer to Dropbox | WetoDrive">
    <meta name="twitter:description" content="Paste a WeTransfer link and the files land in your Dropbox. No downloading, no re-uploading.">

    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="apple-touch-icon" href="{{ asset('favicon.svg') }}">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">
    <meta name="theme-color" content="#2A42F7">

    {{-- JSON_HEX_TAG keeps a "</script>" inside any answer from closing the tag. --}}
    <script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>

    @include('partials.site-analytics')

    @vite(['resources/js/hero.js'])

    @include('partials.site-styles')
    <style>
        .linkbtn {
            background: none; border: 0; padding: 0; font: inherit; color: inherit;
            font-weight: 650; text-decoration: underline; text-underline-offset: 2px; cursor: pointer;
        }
        .linkbtn:hover { color: var(--blue); }
        .perm-note { margin-top: 14px; font-size: .88rem; color: var(--muted); }
    </style>
</head>
<body>

<a href="#transfer" class="skip">Skip to the transfer form</a>

@include('partials.site-nav')

{{-- ============ HERO ============ --}}
<header class="hero">
    <canvas id="heroCanvas" aria-hidden="true"></canvas>
    <div class="wrap">
        @guest
            {{-- Search engines see this version, so it carries the page's keyword. --}}
            <h1>WeTransfer to Dropbox, without downloading a thing</h1>
            <p class="tagline">Paste a WeTransfer link and WetoDrive saves the files straight into your Dropbox. No waiting on a download, no uploading it all again, and no space used on your laptop or phone.</p>
            <a href="{{ route('auth.dropbox') }}" class="btn btn-onblue btn-lg" data-cta="dropbox-hero">Continue with Dropbox</a>
            {{-- Dropbox otherwise signs a browser straight back in as whichever
                 account it already has open, with no chance to pick another. --}}
            <div class="fineprint" style="margin-top:10px;">
                <a href="{{ route('auth.dropbox', ['switch' => 1]) }}" style="color:inherit; font-weight:600;">Use a different Dropbox account</a>
            </div>
            <div class="fineprint">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                No Google account needed, and your first transfer is free. Already use WetoDrive with Google?
                <a href="{{ route('auth.google') }}" style="color:inherit; font-weight:600;">Sign in with Google</a>
            </div>

            {{-- An illustration of the transfer screen, not a record of one. --}}
            <div class="demo" aria-hidden="true">
                <div class="demo-top">
                    <span class="demo-dot"></span>
                    <span class="demo-title">Transfer in progress</span>
                    <span class="demo-file">Pro plan</span>
                </div>
                <div class="demo-name">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <rect x="3" y="3" width="14" height="18" rx="2" fill="#0061FE"/>
                        <path d="M7 9h6M7 13h4" stroke="#fff" stroke-width="1.8" stroke-linecap="round"/>
                    </svg>
                    client_rushes_day2.mov
                </div>
                <div class="demo-bar"><div class="demo-fill"></div></div>
                <div class="demo-foot">
                    <span>18.6 GB</span>
                    <span>streaming to Dropbox</span>
                </div>
                <div class="demo-status">
                    <span class="demo-streaming">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 2v6M12 16v6M2 12h6M16 12h6"/></svg>
                        Nothing stored on your device
                    </span>
                    <span class="demo-saved">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#1E7E38" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                        Saved to your Dropbox
                    </span>
                </div>
            </div>
        @else
            <h1>Save WeTransfer files to Dropbox</h1>
            <p class="tagline">
                @if(Auth::user()->hasDropbox())
                    Paste the link below and the files go straight into your Dropbox.
                @else
                    One step left: connect the Dropbox you want the files in.
                @endif
            </p>

            <div class="box" id="transfer">
                @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif
                @if(session('error'))
                    <div class="alert alert-error">{{ session('error') }}</div>
                @endif

                @unless(Auth::user()->hasDropbox())
                    <div class="box-head"><h2>Connect your Dropbox</h2></div>
                    <p style="color:var(--muted); margin-bottom:18px;">Dropbox will ask you to allow WetoDrive. Once you do, you come straight back here to paste your link.</p>
                    <a href="{{ route('auth.dropbox') }}" class="btn btn-primary btn-lg" style="width:100%;">Connect Dropbox</a>
                    <div class="hint">
                        You can disconnect at any time, from this page or from the connected apps in your Dropbox settings.
                        Want a different Dropbox than the one your browser is signed in to?
                        <a href="{{ route('auth.dropbox', ['switch' => 1]) }}">Use a different Dropbox account</a>.
                    </div>
                @else
                    @php
                        $left = Auth::user()->hasActiveSubscription()
                            ? Auth::user()->activeSubscription->getRemainingTransfers()
                            : max(0, 5 - Auth::user()->total_transfers);
                    @endphp

                    <div class="box-head">
                        <h2>Transfer to Dropbox</h2>
                        <div class="meta">
                            {{ ucfirst(Auth::user()->subscription_tier) }} plan
                            &middot; <span data-transfers-remaining>{{ $left === null ? 'Unlimited' : $left }}</span> left
                            &middot; <span data-total-transfers>{{ Auth::user()->total_transfers }}</span> done
                        </div>
                    </div>

                    @if(Auth::user()->hasTrialTransferAvailable() && $left !== 0)
                        <div class="alert alert-success">You have a one-time 1GB allowance available on this transfer.</div>
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
                            <input type="hidden" id="destination" name="destination" value="dropbox">

                            <label for="wetransfer_url">WeTransfer URL</label>
                            <input type="url" id="wetransfer_url" name="wetransfer_url" required autofocus
                                   value="{{ old('wetransfer_url') }}"
                                   placeholder="https://wetransfer.com/downloads/... or https://we.tl/t-...">
                            <div class="hint" id="urlHint">Works with full wetransfer.com links and short we.tl links.</div>

                            <label for="destination_folder">Dropbox folder <span class="opt">optional</span></label>
                            <input type="text" id="destination_folder" name="destination_folder" autocomplete="off"
                                   value="{{ old('destination_folder') }}" placeholder="Clients/Acme">
                            <div class="hint">Leave empty and files go to the top of your Dropbox. Use / for subfolders; missing ones are created.</div>

                            <button type="submit" class="submit-button" id="transferButton">Transfer to Dropbox</button>
                        </form>

                        <div class="share-cta">
                            Files go to the Dropbox you connected.
                            <form method="POST" action="{{ route('auth.dropbox.disconnect') }}" style="display:inline;">
                                @csrf
                                <button type="submit" class="linkbtn">Disconnect Dropbox</button>
                            </form>
                        </div>
                    </div>

                    {{-- data-resume reattaches to a Dropbox transfer still running after a reload. --}}
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
                @endunless
            </div>
        @endguest
    </div>
</header>

{{-- ============ THREE CARDS ============ --}}
<div class="wrap">
    <div class="cards">
        <div class="card">
            <div class="ico ico-blue">
                <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="#0061FE" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
            </div>
            <h3>Straight into Dropbox</h3>
            <p>No more downloading a WeTransfer to your computer and dragging it into Dropbox. The files go from WeTransfer to your Dropbox directly.</p>
        </div>
        <div class="card">
            <div class="ico ico-green">
                <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="#1E7E38" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="6" width="20" height="12" rx="2"/><path d="M6 14h7"/></svg>
            </div>
            <h3>No space used on your device</h3>
            <p>Nothing lands in your Downloads folder, so a delivery of 40GB of footage never fills up a laptop or a phone on the way to Dropbox.</p>
        </div>
        <div class="card">
            <div class="ico ico-amber">
                <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="#B7791F" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 8v4l3 2"/><circle cx="12" cy="12" r="9"/></svg>
            </div>
            <h3>Checked before it starts</h3>
            <p>Your free Dropbox space is checked before a single file is fetched, so a full Dropbox is flagged in seconds, not after an hour of waiting.</p>
        </div>
    </div>
</div>

{{-- ============ HOW IT WORKS ============ --}}
<section id="how" class="rise">
    <div class="wrap">
        <div class="head">
            <h2>How to save a WeTransfer to Dropbox</h2>
            <p>Three steps the first time. After that it is just the link.</p>
        </div>
        <div class="steps">
            <div class="step">
                <div class="step-n"></div>
                <h3>Continue with Dropbox</h3>
                <p>Allow WetoDrive on Dropbox's own screen. That also sets up your WetoDrive account, so there is no password to create.</p>
            </div>
            <div class="step">
                <div class="step-n"></div>
                <h3>Paste the link</h3>
                <p>Any WeTransfer link works, short we.tl links and full download URLs. Add a folder if you want the files somewhere tidy.</p>
            </div>
            <div class="step">
                <div class="step-n"></div>
                <h3>Walk away</h3>
                <p>Close the tab if you like. The transfer keeps running, and we email you when the files are in your Dropbox.</p>
            </div>
        </div>
    </div>
</section>

{{-- ============ DETAILS ============ --}}
<section class="rise" style="padding-top:0;">
    <div class="wrap">
        <div class="head">
            <h2>Made for real deliveries, not just one small file</h2>
            <p>Rushes, stills, masters and client folders. The things that are too big to download twice.</p>
        </div>
        <div class="cards" style="margin-top:0;">
            <div class="card">
                <h3>Big files take the direct route</h3>
                <p>Files over 1GB stream from WeTransfer to Dropbox in pieces, without ever being saved on our servers. If a connection drops, the upload picks up where it left off.</p>
            </div>
            <div class="card">
                <h3>Your folders, your naming</h3>
                <p>Type a path like Clients/Acme/Day 2 and the folders are created as the files arrive. Large files are saved one by one, ready to open in Dropbox.</p>
            </div>
            <div class="card">
                <h3>Nothing gets overwritten</h3>
                <p>If a file with the same name is already in that folder, Dropbox keeps both and numbers the new one. Your existing work is never replaced.</p>
            </div>
        </div>
    </div>
</section>

{{-- ============ WHY IT'S SAFE ============ --}}
<section id="why" class="rise">
    <div class="wrap perms">
        <div>
            <h2 style="margin-bottom:18px;">What WetoDrive does with your Dropbox</h2>
            <ul class="why" style="list-style:none;">
                <li>
                    {!! $tick !!}
                    <div><b>It only saves what you send</b><span>The connection is used to save the files from your WeTransfer link and to check your free space. WetoDrive does not browse, move, share or delete anything already in your Dropbox.</span></div>
                </li>
                <li>
                    {!! $tick !!}
                    <div><b>Your files are not kept</b><span>Large files stream straight through. Smaller ones pass through a temporary copy that is deleted the moment the upload finishes. We are a pipe, not a warehouse.</span></div>
                </li>
                <li>
                    {!! $tick !!}
                    <div><b>Disconnect whenever you like</b><span>One button revokes our access at Dropbox and deletes the stored token. You can also remove WetoDrive from the connected apps in your Dropbox settings.</span></div>
                </li>
            </ul>
        </div>

        <div>
            <div style="font-size:.82rem; text-transform:uppercase; letter-spacing:.08em; color:var(--muted); margin-bottom:12px;">What Dropbox will ask you to allow</div>
            <div class="perm-list">
                <div class="perm yes">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#2E9E52" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                    <div><b style="font-weight:650;">View and edit files and folders</b> <span style="color:var(--muted); font-size:.84rem;">to save into the folder you choose</span></div>
                </div>
                <div class="perm yes">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#2E9E52" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                    <div><b style="font-weight:650;">View basic account info</b> <span style="color:var(--muted); font-size:.84rem;">to check your free space</span></div>
                </div>
                @foreach (['Delete or move your files', 'Share anything on your behalf', 'Change your account settings'] as $never)
                    <div class="perm no">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>
                        {{ $never }}
                    </div>
                @endforeach
            </div>
            <p class="perm-note">Dropbox groups its permissions broadly, so saving into a folder you pick needs file access. WetoDrive only ever adds files.</p>
        </div>
    </div>
</section>

{{-- ============ FAQ ============ --}}
<section id="faq" class="rise">
    <div class="wrap">
        <div class="head"><h2>WeTransfer to Dropbox: the questions people ask first</h2></div>
        <div class="faq">
            @foreach ($faqs as $i => $qa)
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
            <h2>That WeTransfer link is going to expire</h2>
            <p>Get the files into your Dropbox before it does. The first transfer is on us.</p>
            @auth
                <a href="#transfer" class="btn btn-primary btn-lg">Start a transfer</a>
            @else
                <a href="{{ route('auth.dropbox') }}" class="btn btn-primary btn-lg" data-cta="dropbox-footer-cta">Continue with Dropbox</a>
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
