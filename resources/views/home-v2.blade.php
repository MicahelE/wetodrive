<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    {{-- Title is byte-for-byte the live homepage's. Do not "improve" it: GSC shows
         this page ranks #1 for "wetodrive" and #3 for "wetransfer to google drive",
         and it carries 300 of the site's 409 clicks. --}}
    <title>WetoDrive - WeTransfer to Google Drive</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- The live homepage carries none of the following. Matches SeoController's pattern. --}}
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

    {{-- Staging only. DELETE THIS LINE when this becomes the real homepage,
         or it will deindex the page carrying 73% of the site's search traffic. --}}
    <meta name="robots" content="noindex">

    <link rel="icon" type="image/svg+xml" href="{{ asset('logo-mark.svg') }}">
    <link rel="apple-touch-icon" href="{{ asset('logo-mark.svg') }}">
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

    @vite(['resources/js/hero.js'])

    <style>
        /* ==========================================================
           Design tokens. Three radii and three surfaces, deliberately.
           The previous version had 12 radii and 17 surface tints, which
           is why it read as noise. Do not add a fourth of either.
           ========================================================== */
        :root {
            --blue:      #2A42F7;
            --blue-dark: #1A2D99;
            --blue-soft: #EEF1FF;

            --ink:       #171A26;
            --muted:     #5B6478;
            --line:      #E3E7F2;
            --page:      #F7F8FC;
            --white:     #FFFFFF;
            --ink-deep:  #14161F;   /* footer */

            --r-sm:   8px;
            --r:      14px;
            --r-full: 999px;

            --shadow:    0 1px 2px rgba(23,26,38,.05), 0 8px 24px rgba(23,26,38,.06);
            --shadow-lg: 0 2px 4px rgba(23,26,38,.06), 0 16px 40px rgba(23,26,38,.10);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        html { scroll-behavior: smooth; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--page);
            color: var(--ink);
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
        }

        .wrap { max-width: 1080px; margin: 0 auto; padding: 0 20px; }

        h1, h2, h3 { line-height: 1.2; letter-spacing: -.02em; }
        h2 { font-size: clamp(1.6rem, 3.4vw, 2.1rem); font-weight: 700; }
        a { color: inherit; }

        /* Sticky nav is 64px; keep anchor targets clear of it. */
        section[id], div[id="transfer"] { scroll-margin-top: 80px; }

        /* ============ Nav ============ */
        .nav {
            background: var(--white); border-bottom: 1px solid var(--line);
            position: sticky; top: 0; z-index: 50;
        }
        .nav-in { display: flex; align-items: center; justify-content: space-between; height: 64px; gap: 16px; }
        .brand { display: flex; align-items: center; gap: 10px; text-decoration: none; font-weight: 700; font-size: 1.1rem; }
        /* The wordmark must be ONE flex item, or the gap opens up inside the name
           and it reads as "Weto Drive". */
        .brand-name { white-space: nowrap; }
        .brand-name em { font-style: normal; color: var(--blue); }
        .foot .brand-name em { color: #8FA2FF; }

        .nav-links { display: flex; align-items: center; gap: 26px; }
        .nav-links a { color: var(--muted); text-decoration: none; font-size: .93rem; font-weight: 500; padding: 8px 0; }
        .nav-links a:hover { color: var(--ink); }

        /* ============ Buttons ============ */
        .btn {
            display: inline-flex; align-items: center; justify-content: center; gap: 9px;
            padding: 12px 20px; border-radius: var(--r-sm); font-size: .95rem; font-weight: 600;
            text-decoration: none; border: 1px solid transparent; cursor: pointer;
            font-family: inherit; transition: background .18s, border-color .18s, color .18s;
            min-height: 44px;
        }
        .btn-primary { background: var(--blue); color: #fff; }
        .btn-primary:hover { background: #2035d6; }
        .btn-outline { background: var(--white); color: var(--ink); border-color: var(--line); }
        .btn-outline:hover { border-color: #C7CEE4; }
        .btn-onblue { background: var(--white); color: var(--blue-dark); }
        .btn-onblue:hover { background: #F0F2FF; }
        .btn-lg { padding: 15px 26px; font-size: 1rem; }

        /* ============ Hero ============ */
        .hero {
            position: relative; isolation: isolate;
            background: linear-gradient(135deg, var(--blue) 0%, var(--blue-dark) 100%);
            color: #fff; padding: 68px 0 104px; text-align: center;
            overflow: hidden;
        }
        /* The three.js stream sits behind the copy. The gradient above is the
           fallback, so the hero is complete before (and without) WebGL. */
        #heroCanvas {
            position: absolute; inset: 0; width: 100%; height: 100%;
            z-index: -1; display: block; pointer-events: none;
        }
        .hero > .wrap { position: relative; z-index: 1; }

        /* ============ Live product demo ============ */
        /* This is the real transfer panel rebuilt in markup rather than a
           screenshot: identical styling, sharp on any display, animated, and it
           costs bytes we were going to spend on the HTML anyway. A photo of the
           same thing would have been 50-200KB on a server with no compression. */
        .demo {
            background: var(--white); color: var(--ink); border-radius: var(--r);
            padding: 20px; max-width: 520px; margin: 34px auto 0;
            box-shadow: var(--shadow-lg); text-align: left;
        }
        .demo-top { display: flex; align-items: center; gap: 10px; margin-bottom: 14px; }
        .demo-dot { width: 9px; height: 9px; border-radius: var(--r-full); background: #34A853; }
        .demo-title { font-weight: 650; font-size: .93rem; }
        .demo-file { margin-left: auto; font-size: .8rem; color: var(--muted); font-variant-numeric: tabular-nums; }
        .demo-name { font-size: .92rem; font-weight: 600; margin-bottom: 9px; display: flex; align-items: center; gap: 8px; }
        .demo-bar { background: var(--blue-soft); border-radius: var(--r-full); height: 10px; overflow: hidden; }
        .demo-fill {
            height: 100%; width: 0%; border-radius: var(--r-full);
            background: linear-gradient(90deg, #4285F4, #34A853);
            animation: fill 7s cubic-bezier(.25,.7,.35,1) infinite;
        }
        @keyframes fill { 0% { width: 0; } 78% { width: 100%; } 100% { width: 100%; } }
        .demo-foot { display: flex; justify-content: space-between; margin-top: 10px; font-size: .8rem; color: var(--muted); }
        /* Both status lines share one fixed-height slot and cross-fade. Reserving
           the row avoids layout shift; keeping something in it at all times avoids
           the dead gap the panel had while "Saved" was still invisible. */
        .demo-status {
            position: relative; height: 21px; margin-top: 13px; padding-top: 12px;
            border-top: 1px solid var(--line);
        }
        .demo-status > span {
            position: absolute; left: 0; right: 0; bottom: 0;
            display: flex; align-items: center; gap: 8px; font-size: .85rem; font-weight: 600;
        }
        .demo-streaming { color: var(--muted); animation: swapOut 7s linear infinite; }
        .demo-saved { color: #1E7E38; opacity: 0; animation: swapIn 7s linear infinite; }
        @keyframes swapOut { 0%, 74% { opacity: 1; } 80%, 100% { opacity: 0; } }
        @keyframes swapIn  { 0%, 74% { opacity: 0; } 80%, 100% { opacity: 1; } }

        /* ============ Audience marquee ============ */
        /* CSS-only infinite scroll: the track holds the list twice and slides by
           exactly half, so the seam is invisible and it needs no JS. */
        .marquee {
            background: var(--white); border-block: 1px solid var(--line);
            padding: 16px 0; overflow: hidden;
            -webkit-mask-image: linear-gradient(90deg, transparent, #000 9%, #000 91%, transparent);
            mask-image: linear-gradient(90deg, transparent, #000 9%, #000 91%, transparent);
        }
        .marquee-track { display: flex; width: max-content; animation: slide 46s linear infinite; }
        .marquee:hover .marquee-track { animation-play-state: paused; }
        @keyframes slide { to { transform: translateX(-50%); } }
        .marquee-item {
            display: inline-flex; align-items: center; gap: 9px;
            padding: 0 26px; font-size: .96rem; font-weight: 600; color: var(--ink);
            white-space: nowrap;
        }
        .marquee-item svg { flex-shrink: 0; }

        /* ============ Motion, used sparingly ============ */
        .rise { opacity: 0; transform: translateY(16px); transition: opacity .55s ease, transform .55s ease; }
        .rise.in { opacity: 1; transform: none; }
        .card, .quote { transition: transform .22s ease, box-shadow .22s ease; }
        .card:hover, .quote:hover { transform: translateY(-3px); box-shadow: var(--shadow-lg); }
        .hero h1 { font-size: clamp(2rem, 5vw, 3rem); font-weight: 800; margin-bottom: 14px; }
        .hero .tagline { font-size: clamp(1.02rem, 2.2vw, 1.2rem); opacity: .9; max-width: 620px; margin: 0 auto 30px; }
        .hero .fineprint { margin-top: 16px; font-size: .87rem; opacity: .82; display: flex; align-items: center; justify-content: center; gap: 7px; flex-wrap: wrap; }

        /* The transfer box: the reason the page exists, so it sits in the hero
           rather than a screen below it. */
        .box {
            background: var(--white); color: var(--ink); border-radius: var(--r);
            padding: 24px; max-width: 620px; margin: 0 auto; box-shadow: var(--shadow-lg);
            text-align: left;
        }
        .box-head { display: flex; justify-content: space-between; align-items: baseline; gap: 12px; margin-bottom: 16px; flex-wrap: wrap; }
        .box-head h2 { font-size: 1.15rem; }
        .box-head .meta { font-size: .85rem; color: var(--muted); }

        label { display: block; font-size: .87rem; font-weight: 600; margin-bottom: 7px; }
        input[type=url] {
            width: 100%; padding: 14px 15px; border-radius: var(--r-sm);
            border: 1px solid var(--line); font-size: 16px; font-family: inherit; color: var(--ink);
            background: var(--white); transition: border-color .18s, box-shadow .18s;
        }
        input[type=url]::placeholder { color: #9AA3B8; }
        input[type=url]:focus { outline: none; border-color: var(--blue); box-shadow: 0 0 0 3px rgba(42,66,247,.15); }
        .submit-button {
            width: 100%; margin-top: 12px; padding: 15px; border-radius: var(--r-sm);
            background: var(--blue); color: #fff; border: 0; font-size: 1rem; font-weight: 650;
            font-family: inherit; cursor: pointer; min-height: 48px; transition: background .18s;
        }
        .submit-button:hover:not(:disabled) { background: #2035d6; }
        .submit-button:disabled { opacity: .55; cursor: not-allowed; }
        .hint { margin-top: 9px; font-size: .84rem; color: var(--muted); }
        .hint.bad { color: #C0392B; }

        /* ============ Cards overlapping the hero ============ */
        .cards { display: grid; grid-template-columns: repeat(3, 1fr); gap: 18px; margin-top: 44px; }
        .card {
            background: var(--white); border: 1px solid var(--line); border-radius: var(--r);
            padding: 24px; box-shadow: var(--shadow);
        }
        /* Icons are full colour rather than a single blue line weight: the flat
           monochrome set read as dry next to the emoji on the old homepage. */
        .card .ico {
            width: 52px; height: 52px; border-radius: 12px;
            display: grid; place-items: center; margin-bottom: 15px;
            transition: transform .25s ease;
        }
        .card:hover .ico { transform: scale(1.06) rotate(-3deg); }
        .ico-amber { background: linear-gradient(145deg, #FFF4D6, #FFE7A8); }
        .ico-green { background: linear-gradient(145deg, #DFF6E6, #BEEBCC); }
        .ico-blue  { background: linear-gradient(145deg, #E0E8FF, #C4D3FF); }
        .card h3 { font-size: 1.02rem; font-weight: 650; margin-bottom: 7px; }
        .card p { font-size: .93rem; color: var(--muted); }

        /* ============ Generic section ============ */
        section { padding: 62px 0; }
        .head { text-align: center; max-width: 620px; margin: 0 auto 38px; }
        .head p { color: var(--muted); margin-top: 10px; }

        /* ============ Steps ============ */
        .steps { display: grid; grid-template-columns: repeat(3, 1fr); gap: 30px; counter-reset: s; }
        .step { text-align: center; }
        .step-n {
            counter-increment: s; width: 34px; height: 34px; border-radius: var(--r-full);
            background: var(--blue); color: #fff; display: grid; place-items: center;
            font-weight: 700; font-size: .92rem; margin: 0 auto 13px;
        }
        .step-n::before { content: counter(s); }
        .step h3 { font-size: 1rem; margin-bottom: 6px; }
        .step p { font-size: .93rem; color: var(--muted); }

        /* ============ Stats strip ============ */
        .stats { display: flex; justify-content: center; gap: 46px; flex-wrap: wrap; text-align: center; }
        .stat b { display: block; font-size: 1.7rem; font-weight: 700; letter-spacing: -.02em; }
        .stat span { font-size: .87rem; color: var(--muted); }

        /* ============ Quotes ============ */
        .quotes { display: grid; grid-template-columns: repeat(auto-fit, minmax(min(300px, 100%), 1fr)); gap: 18px; }
        .quote { background: var(--white); border: 1px solid var(--line); border-radius: var(--r); padding: 24px; }
        .quote blockquote { font-size: 1.02rem; color: var(--ink); }
        .quote figcaption { display: flex; align-items: center; gap: 11px; margin-top: 18px; }
        .avatar {
            width: 36px; height: 36px; border-radius: var(--r-full); background: var(--blue-soft);
            color: var(--blue); display: grid; place-items: center; font-weight: 700; font-size: .92rem; flex-shrink: 0;
        }
        .quote b { display: block; font-size: .92rem; }
        .quote small { color: var(--muted); font-size: .84rem; }

        /* ============ Permissions ============ */
        .perms { display: grid; grid-template-columns: 1fr 1fr; gap: 36px; align-items: start; }
        .perm-list { list-style: none; display: grid; gap: 11px; }
        .perm {
            display: flex; align-items: center; gap: 11px; padding: 13px 15px;
            border-radius: var(--r-sm); border: 1px solid var(--line); background: var(--white); font-size: .93rem;
        }
        .perm.yes { border-color: #BFE3C9; background: #F2FBF5; }
        .perm.no { color: var(--muted); }
        .why li { display: flex; gap: 12px; margin-bottom: 16px; }
        .why b { display: block; font-size: .98rem; margin-bottom: 2px; }
        .why span { color: var(--muted); font-size: .92rem; }
        .tick { flex-shrink: 0; color: #2E9E52; margin-top: 3px; }

        /* ============ FAQ ============ */
        .faq { max-width: 720px; margin: 0 auto; display: grid; gap: 10px; }
        .qa { background: var(--white); border: 1px solid var(--line); border-radius: var(--r-sm); }
        .qa summary {
            padding: 16px 18px; cursor: pointer; font-weight: 600; list-style: none;
            display: flex; justify-content: space-between; align-items: center; gap: 14px; min-height: 44px;
        }
        .qa summary::-webkit-details-marker { display: none; }
        .qa summary::after { content: '+'; font-size: 1.35rem; color: var(--muted); font-weight: 400; line-height: 1; }
        .qa[open] summary::after { content: '\2013'; }
        .qa p { padding: 0 18px 16px; color: var(--muted); font-size: .94rem; }

        /* ============ CTA ============ */
        .cta {
            background: var(--blue-soft); border: 1px solid #D9E0FF; border-radius: var(--r);
            padding: 44px 28px; text-align: center;
        }
        .cta p { color: var(--muted); margin: 10px auto 22px; max-width: 440px; }

        /* ============ Footer ============ */
        footer { background: var(--ink-deep); color: #A8B0C4; padding: 48px 0 28px; }
        .foot { display: grid; grid-template-columns: 1.7fr 1fr 1fr 1fr; gap: 34px; }
        .foot h4 { color: #fff; font-size: .84rem; text-transform: uppercase; letter-spacing: .08em; margin-bottom: 13px; }
        .foot a { display: block; color: #A8B0C4; text-decoration: none; font-size: .91rem; margin-bottom: 9px; padding: 3px 0; }
        .foot a:hover { color: #fff; }
        .foot-about p { font-size: .91rem; margin: 12px 0 16px; max-width: 290px; }
        .foot-bottom { margin-top: 34px; padding-top: 20px; border-top: 1px solid #262A38; text-align: center; font-size: .86rem; color: #7A8299; }
        .foot .brand { color: #fff; }

        /* ============ Alerts ============ */
        .alert { padding: 13px 15px; border-radius: var(--r-sm); margin-bottom: 14px; font-size: .93rem; }
        .alert-success { background: #F2FBF5; border: 1px solid #BFE3C9; color: #1E6B39; }
        .alert-error { background: #FDF3F2; border: 1px solid #F0C8C4; color: #A5342A; }

        /* ============ Progress (transfer in flight) ============ */
        .bar-track { background: var(--blue-soft); border-radius: var(--r-full); height: 22px; overflow: hidden; }
        #progressBar { background: var(--blue); height: 100%; width: 0%; transition: width .35s ease; display: flex; align-items: center; justify-content: center; position: relative; }
        #progressPercent { color: #fff; font-weight: 650; font-size: .8rem; position: absolute; }
        .pgrid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin: 14px 0; }
        .pcell { border: 1px solid var(--line); border-radius: var(--r-sm); padding: 12px; text-align: center; }
        .pcell b { display: block; font-size: 1.05rem; }
        .pcell span { font-size: .8rem; color: var(--muted); }

        /* ============ Mobile ============ */
        .menu { display: none; }
        .menu > summary {
            list-style: none; cursor: pointer; width: 44px; height: 44px;
            display: grid; place-items: center; border-radius: var(--r-sm); border: 1px solid var(--line);
        }
        .menu > summary::-webkit-details-marker { display: none; }
        .menu[open] > summary { background: var(--blue-soft); border-color: #D9E0FF; }
        .menu-panel {
            position: absolute; left: 0; right: 0; top: 64px; background: var(--white);
            border-bottom: 1px solid var(--line); padding: 10px 20px 16px; box-shadow: var(--shadow);
        }
        .menu-panel a { display: block; padding: 13px 0; text-decoration: none; color: var(--ink); border-bottom: 1px solid var(--line); font-weight: 500; }
        .menu-panel a:last-child { border-bottom: 0; }

        @media (max-width: 860px) {
            .cards, .steps, .perms { grid-template-columns: 1fr; }
            .cards { margin-top: 30px; }
            .foot { grid-template-columns: 1fr 1fr; }
            .nav-links { display: none; }
            .menu { display: block; }
            section { padding: 46px 0; }
            .hero { padding: 44px 0 84px; }
        }
        @media (max-width: 520px) {
            .foot { grid-template-columns: 1fr; }
            .box { padding: 18px; }
            .stats { gap: 26px; }
        }

        /* ============ Accessibility ============ */
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { animation-duration: .001ms !important; transition-duration: .001ms !important; scroll-behavior: auto !important; }
            /* Anything that animates in must still be readable when motion is off. */
            .rise { opacity: 1 !important; transform: none !important; }
            .marquee-track { animation: none; }
        }
        :focus-visible { outline: 2px solid var(--blue); outline-offset: 2px; border-radius: 4px; }
        .skip {
            position: absolute; left: -9999px; background: var(--white); padding: 12px 16px;
            border-radius: var(--r-sm); z-index: 100;
        }
        .skip:focus { left: 16px; top: 12px; }
    </style>
</head>
<body>

<a href="#transfer" class="skip">Skip to the transfer form</a>

{{-- ============ NAV ============ --}}
<nav class="nav">
    <div class="wrap nav-in">
        <a href="{{ route('home.v2') }}" class="brand">
            @include('partials.logo-mark', ['size' => 30])
            <span class="brand-name">Weto<em>Drive</em></span>
        </a>

        <div class="nav-links">
            <a href="#how">How it works</a>
            <a href="#why">Why it's safe</a>
            <a href="#faq">FAQ</a>
            <a href="{{ route('subscription.pricing') }}">Pricing</a>
        </div>

        <div style="display:flex; align-items:center; gap:10px;">
            @auth
                <a href="{{ route('subscription.manage') }}" class="btn btn-outline">Dashboard</a>
            @else
                <a href="{{ route('auth.google') }}" class="btn btn-primary">Sign In</a>
            @endauth

            {{-- Native disclosure widget, so the mobile menu needs no JS at all.
                 The previous version styled a .burger that was never in the markup,
                 which left phones with no navigation whatsoever. --}}
            <details class="menu">
                <summary aria-label="Menu">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M3 6h18M3 12h18M3 18h18"/></svg>
                </summary>
                <div class="menu-panel">
                    <a href="#how">How it works</a>
                    <a href="#why">Why it's safe</a>
                    <a href="#faq">FAQ</a>
                    <a href="{{ route('subscription.pricing') }}">Pricing</a>
                </div>
            </details>
        </div>
    </div>
</nav>

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
                    <div class="alert alert-success">You have a one-time 3GB allowance available on this transfer.</div>
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
                        <button type="submit" class="submit-button" id="transferButton">Transfer to Google Drive</button>
                    </form>
                </div>

                <div id="progressContainer" style="display:none;">
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
                        Transfer in progress. You can keep this tab open.
                    </div>
                    <div id="completionMessage" style="display:none; margin-top:14px;"></div>
                </div>
            </div>
        @else
            <h1>For people who live in WeTransfer</h1>
            <p class="tagline">Transfer files from WeTransfer to Google Drive instantly. If you take delivery of rushes, stills and masters all week, WetoDrive moves them into Drive without downloading and uploading, and without using storage on your device.</p>
            <a href="{{ route('auth.google') }}" class="btn btn-onblue btn-lg">
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
                ['How big a file can I move?', 'Your very first transfer stretches to 3GB. Pro handles up to 25GB and Premium goes to 500GB.'],
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
                <a href="{{ route('auth.google') }}" class="btn btn-primary btn-lg">Get Started with Google Drive</a>
            @endauth
        </div>
    </div>
</section>

{{-- ============ FOOTER ============ --}}
<footer>
    <div class="wrap">
        <div class="foot">
            <div class="foot-about">
                <a href="{{ route('home.v2') }}" class="brand" style="color:#fff;">
                    @include('partials.logo-mark', ['size' => 26])
                    <span class="brand-name">Weto<em>Drive</em></span>
                </a>
                <p>Transfer files from WeTransfer to Google Drive instantly. No downloads, no storage limits on your device.</p>
                <a href="https://www.producthunt.com/products/wetodrive?embed=true&utm_source=badge-featured" target="_blank" rel="noopener" style="margin:0;">
                    <img src="https://api.producthunt.com/widgets/embed-image/v1/featured.svg?post_id=1029974&theme=dark&t=1761306053608"
                         alt="WetoDrive on Product Hunt" width="210" height="45" style="max-width:100%;">
                </a>
            </div>

            {{-- Anchor text copied verbatim from the live footer: these are the only
                 internal links the SEO landing pages get from the homepage. --}}
            <div>
                <h4>Quick Links</h4>
                <a href="{{ route('home') }}">Home</a>
                <a href="{{ route('subscription.pricing') }}">Pricing</a>
                @auth <a href="{{ route('subscription.manage') }}">Dashboard</a>
                @else <a href="{{ route('auth.google') }}">Sign In</a> @endauth
            </div>

            <div>
                <h4>WeTransfer Guides</h4>
                <a href="{{ route('seo.pricing') }}">WeTransfer Pricing</a>
                <a href="{{ route('seo.send-files') }}">How to Send Files</a>
                <a href="{{ route('seo.upload') }}">Upload Tutorial</a>
                <a href="{{ route('seo.free') }}">Free Plan Guide</a>
                <a href="{{ route('seo.alternative') }}">WeTransfer Alternative</a>
                <a href="{{ route('seo.google-drive-guide') }}">Save to Google Drive</a>
            </div>

            <div>
                <h4>Support</h4>
                <a href="{{ route('support.help') }}">Help Center</a>
                <a href="{{ route('support.contact') }}">Contact Us</a>
                <a href="{{ route('support.privacy') }}">Privacy Policy</a>
                <a href="{{ route('support.terms') }}">Terms of Service</a>
            </div>
        </div>

        <div class="foot-bottom">&copy; {{ date('Y') }} WetoDrive. All rights reserved.</div>
    </div>
</footer>

<script>
    // Tell people the link is wrong before they submit it, not after a round trip.
    (function () {
        const input = document.getElementById('wetransfer_url');
        if (!input) return;
        const hint = document.getElementById('urlHint');
        const ok = /(^|\.)wetransfer\.com\//i, short = /(^|\/\/)we\.tl\//i;
        input.addEventListener('input', function () {
            const v = input.value.trim();
            const bad = v.length > 8 && !ok.test(v) && !short.test(v);
            hint.classList.toggle('bad', bad);
            hint.textContent = bad
                ? "That does not look like a WeTransfer link. It should start with wetransfer.com or we.tl."
                : "Works with full wetransfer.com links and short we.tl links.";
        });
    })();

    // Reveal decorative sections on scroll. Never applied to the transfer form,
    // which must be visible whether or not this script runs.
    (function () {
        const els = document.querySelectorAll('.rise');
        if (!els.length || !('IntersectionObserver' in window)) {
            els.forEach(el => el.classList.add('in'));
            return;
        }
        const io = new IntersectionObserver((entries) => {
            entries.forEach(e => {
                if (e.isIntersecting) { e.target.classList.add('in'); io.unobserve(e.target); }
            });
        }, { threshold: .08, rootMargin: '0px 0px -50px 0px' });
        els.forEach(el => io.observe(el));
    })();

    @include('partials.transfer-script')
</script>

</body>
</html>
