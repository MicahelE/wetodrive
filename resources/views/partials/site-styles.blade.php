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
        /* Starts paused. Lighthouse measures Speed Index from how quickly the
           viewport stops changing, and a strip sliding through the first paint
           keeps it from ever settling: it cost 2.5s -> 4.4s. The .go class is
           added once the page is idle, and it pauses again when scrolled past. */
        .marquee-track { display: flex; width: max-content; animation: slide 46s linear infinite; animation-play-state: paused; }
        .marquee-track.go { animation-play-state: running; }
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
        label .opt { font-weight: 400; color: var(--muted); }
        /* The folder field shares the URL field's styling; both are plain text boxes. */
        input[type=url], input[type=text] {
            width: 100%; padding: 14px 15px; border-radius: var(--r-sm);
            border: 1px solid var(--line); font-size: 16px; font-family: inherit; color: var(--ink);
            background: var(--white); transition: border-color .18s, box-shadow .18s;
        }
        input[type=url]::placeholder, input[type=text]::placeholder { color: #9AA3B8; }
        input[type=url]:focus, input[type=text]:focus { outline: none; border-color: var(--blue); box-shadow: 0 0 0 3px rgba(42,66,247,.15); }
        .share-cta {
            margin-top: 18px; padding-top: 16px; border-top: 1px solid var(--line, #e6e8eb);
            font-size: .9rem; color: var(--muted, #6c757d); text-align: center;
        }
        .share-cta a { color: inherit; font-weight: 650; text-decoration: underline; text-underline-offset: 2px; }
        .share-cta a:hover { color: var(--brand, #2a42f7); }
        #destination_folder { margin-bottom: 2px; }
        label[for=destination_folder] { margin-top: 16px; }
        .folder-row { display: flex; gap: 8px; align-items: stretch; }
        .folder-row input[type=text] { flex: 1 1 auto; min-width: 0; }
        .browse-btn {
            flex: 0 0 auto; font: inherit; font-size: .9rem; font-weight: 600;
            padding: 0 16px; border-radius: var(--r-sm); cursor: pointer;
            background: var(--white); color: var(--blue); border: 1px solid var(--line);
            transition: border-color .18s, background .18s;
        }
        .browse-btn:hover { border-color: var(--blue); background: rgba(42,66,247,.05); }
        .browse-btn:disabled { opacity: .6; cursor: wait; }
        /* Google positions its picker absolutely, at an offset worked out from the
           document top. On a page this tall that can put the dialog far above the
           viewport, so the user clicks Browse Drive and sees nothing at all.
           Pinning it to the viewport makes it immune to the scroll position. */
        .picker-dialog-bg { position: fixed !important; inset: 0 !important; height: 100% !important; }
        .picker-dialog {
            position: fixed !important; top: 50% !important; left: 50% !important;
            transform: translate(-50%, -50%) !important; margin: 0 !important;
            max-width: calc(100vw - 32px) !important; max-height: calc(100vh - 32px) !important;
        }
        @media (max-width: 520px) { .folder-row { flex-wrap: wrap; } .browse-btn { width: 100%; padding: 12px; } }
        .folder-chip {
            font: inherit; font-size: .82rem; color: var(--blue); background: rgba(42,66,247,.07);
            border: 1px solid rgba(42,66,247,.18); border-radius: 999px; padding: 3px 10px;
            margin: 0 4px 4px 0; cursor: pointer;
        }
        .folder-chip:hover { background: rgba(42,66,247,.13); }
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

        /* ============ Account menu ============ */
        /* The redesign dropped the Disconnect button and the Admin link that the
           live homepage carries, which left a signed-in user with no way to sign
           out. Native <details> again, so it needs no JS. */
        .acct { position: relative; }
        .acct > summary {
            list-style: none; cursor: pointer; display: flex; align-items: center; gap: 8px;
            padding: 6px 10px 6px 6px; border-radius: var(--r-full); border: 1px solid var(--line);
            min-height: 44px; font-size: .92rem; font-weight: 600;
        }
        .acct > summary::-webkit-details-marker { display: none; }
        .acct[open] > summary { background: var(--blue-soft); border-color: #D9E0FF; }
        .acct-avatar {
            width: 28px; height: 28px; border-radius: var(--r-full); flex-shrink: 0;
            background: var(--blue); color: #fff; display: grid; place-items: center;
            font-size: .82rem; font-weight: 700;
        }
        .acct-panel {
            position: absolute; right: 0; top: calc(100% + 8px); min-width: 232px;
            background: var(--white); border: 1px solid var(--line); border-radius: var(--r);
            box-shadow: var(--shadow-lg); padding: 6px; z-index: 60;
        }
        .acct-who { padding: 10px 12px 12px; border-bottom: 1px solid var(--line); margin-bottom: 6px; }
        .acct-who b { display: block; font-size: .92rem; }
        .acct-who span { color: var(--muted); font-size: .82rem; word-break: break-all; }
        .acct-panel a, .acct-panel button {
            display: block; width: 100%; text-align: left; padding: 11px 12px; border-radius: var(--r-sm);
            text-decoration: none; color: var(--ink); font-size: .92rem; font-family: inherit;
            background: none; border: 0; cursor: pointer; font-weight: 500;
        }
        .acct-panel a:hover, .acct-panel button:hover { background: var(--page); }
        .acct-panel button { color: #B3261E; }

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
