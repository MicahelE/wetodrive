<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    {{-- Title is byte-for-byte the live homepage's. Do not "improve" it: it is the
         string currently ranking, and this page is meant to replace that one. --}}
    <title>WetoDrive - WeTransfer to Google Drive</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- The live homepage carries none of the following. The SEO landing pages all do
         (see SeoController), so this matches their pattern rather than inventing one. --}}
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

    {{-- Staging only. Delete this line at the moment this becomes the real homepage,
         otherwise it will deindex the page we are trying to preserve rankings for. --}}
    <meta name="robots" content="noindex">

    {{-- No favicon.ico link: the file is an svg with an .ico extension and 404s,
         so the live homepage's reference to it is a wasted request. --}}
    <link rel="icon" type="image/svg+xml" href="{{ asset('logo-mark.svg') }}">
    <link rel="apple-touch-icon" href="{{ asset('logo-mark.svg') }}">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">
    <meta name="theme-color" content="#0A0F24">

    {{-- The @@ escapes are required: Blade reads a bare @context / @type as a
         directive and refuses to compile the page. @@ emits a literal @. --}}
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

    <style>
        :root {
            --bg:        #070B18;
            --bg-2:      #0C1230;
            --surface:   rgba(255,255,255,.045);
            --surface-2: rgba(255,255,255,.075);
            --stroke:    rgba(255,255,255,.10);
            --stroke-2:  rgba(255,255,255,.18);
            --text:      #EEF2FF;
            --muted:     #9AA6C4;
            --indigo:    #6366F1;
            --blue:      #3B82F6;
            --g-blue:    #4285F4;
            --g-green:   #34A853;
            --g-yellow:  #FBBC04;
            --g-red:     #EA4335;
            --radius:    20px;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        html { scroll-behavior: smooth; }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--bg);
            color: var(--text);
            line-height: 1.6;
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
        }

        /* ============ Animated backdrop ============ */
        .backdrop { position: fixed; inset: 0; z-index: -1; overflow: hidden; background: var(--bg); }

        .aurora {
            position: absolute;
            border-radius: 50%;
            filter: blur(90px);
            opacity: .5;
            will-change: transform;
        }
        .aurora.a { width: 620px; height: 620px; background: #4F46E5; top: -220px; left: -160px; animation: drift-a 22s ease-in-out infinite; }
        .aurora.b { width: 540px; height: 540px; background: #2563EB; top: 6%;  right: -180px; animation: drift-b 26s ease-in-out infinite; }
        .aurora.c { width: 460px; height: 460px; background: #0EA5E9; top: 52%; left: 26%;    animation: drift-c 30s ease-in-out infinite; opacity: .32; }

        @keyframes drift-a { 0%,100% { transform: translate(0,0) scale(1); }    50% { transform: translate(120px, 90px) scale(1.14); } }
        @keyframes drift-b { 0%,100% { transform: translate(0,0) scale(1); }    50% { transform: translate(-130px, 70px) scale(1.1); } }
        @keyframes drift-c { 0%,100% { transform: translate(0,0) scale(1); }    50% { transform: translate(70px, -110px) scale(1.2); } }

        /* faint grid for depth */
        .grid-overlay {
            position: absolute; inset: 0;
            background-image:
                linear-gradient(rgba(255,255,255,.028) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,.028) 1px, transparent 1px);
            background-size: 56px 56px;
            mask-image: radial-gradient(ellipse 90% 60% at 50% 0%, #000 40%, transparent 100%);
            -webkit-mask-image: radial-gradient(ellipse 90% 60% at 50% 0%, #000 40%, transparent 100%);
        }

        /* ============ Layout ============ */
        .wrap { max-width: 1180px; margin: 0 auto; padding: 0 24px; }

        section { position: relative; padding: 96px 0; }
        .section-head { text-align: center; max-width: 680px; margin: 0 auto 56px; }
        .eyebrow {
            display: inline-block; font-size: .78rem; font-weight: 600; letter-spacing: .12em;
            text-transform: uppercase; color: #A5B4FC; margin-bottom: 14px;
        }
        h2 { font-size: clamp(1.9rem, 4vw, 2.7rem); line-height: 1.15; letter-spacing: -.025em; font-weight: 700; }
        .section-head p { color: var(--muted); margin-top: 14px; font-size: 1.06rem; }

        /* ============ Nav ============ */
        .nav {
            position: sticky; top: 0; z-index: 100;
            backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px);
            background: rgba(7,11,24,.62);
            border-bottom: 1px solid transparent;
            transition: border-color .3s, background .3s;
        }
        .nav.scrolled { border-bottom-color: var(--stroke); background: rgba(7,11,24,.86); }
        .nav-in { display: flex; align-items: center; justify-content: space-between; height: 68px; }

        .brand { display: flex; align-items: center; gap: 11px; text-decoration: none; color: var(--text); }
        .brand-name { font-weight: 700; font-size: 1.16rem; letter-spacing: -.02em; }
        .brand-name span { color: #93B4FF; }

        .nav-links { display: flex; align-items: center; gap: 30px; }
        .nav-links a { color: var(--muted); text-decoration: none; font-size: .94rem; font-weight: 500; transition: color .2s; }
        .nav-links a:hover { color: var(--text); }

        /* ============ Buttons ============ */
        .btn {
            display: inline-flex; align-items: center; justify-content: center; gap: 9px;
            padding: 13px 24px; border-radius: 13px; font-size: .96rem; font-weight: 600;
            text-decoration: none; border: 0; cursor: pointer; position: relative; overflow: hidden;
            transition: transform .22s cubic-bezier(.34,1.56,.64,1), box-shadow .22s;
            font-family: inherit;
        }
        .btn-primary {
            background: linear-gradient(135deg, #6366F1, #2563EB);
            color: #fff; box-shadow: 0 6px 22px rgba(79,70,229,.42);
        }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 12px 34px rgba(79,70,229,.55); }
        /* sheen sweep */
        .btn-primary::after {
            content: ''; position: absolute; top: 0; left: -120%; width: 60%; height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,.28), transparent);
            transform: skewX(-20deg);
        }
        .btn-primary:hover::after { animation: sheen .8s ease; }
        @keyframes sheen { to { left: 140%; } }

        .btn-ghost {
            background: var(--surface); color: var(--text); border: 1px solid var(--stroke-2);
            backdrop-filter: blur(8px);
        }
        .btn-ghost:hover { background: var(--surface-2); transform: translateY(-2px); }
        .btn-lg { padding: 16px 30px; font-size: 1.03rem; border-radius: 15px; }

        /* ============ Hero ============ */
        .hero { padding: 76px 0 88px; }
        .hero-grid { display: grid; grid-template-columns: 1.05fr .95fr; gap: 58px; align-items: center; }

        .pill {
            display: inline-flex; align-items: center; gap: 9px; padding: 7px 15px 7px 9px;
            border-radius: 999px; background: var(--surface); border: 1px solid var(--stroke-2);
            font-size: .84rem; color: #C7D2FE; margin-bottom: 24px; backdrop-filter: blur(8px);
        }
        .pill .dot { width: 7px; height: 7px; border-radius: 50%; background: var(--g-green); box-shadow: 0 0 0 0 rgba(52,168,83,.7); animation: pulse-dot 2.2s infinite; }
        @keyframes pulse-dot { 70% { box-shadow: 0 0 0 8px rgba(52,168,83,0); } 100% { box-shadow: 0 0 0 0 rgba(52,168,83,0); } }

        h1 {
            font-size: clamp(2.5rem, 5.4vw, 4rem); line-height: 1.05;
            letter-spacing: -.038em; font-weight: 800; margin-bottom: 22px;
        }
        .grad {
            background: linear-gradient(115deg, #A5B4FC 0%, #60A5FA 45%, #34D399 100%);
            -webkit-background-clip: text; background-clip: text; color: transparent;
        }
        .lede { font-size: 1.14rem; color: var(--muted); max-width: 500px; margin-bottom: 32px; }
        /* Keep the first line intact where there's room; let it wrap on small phones. */
        @media (min-width: 420px) { .nw { white-space: nowrap; } }

        .hero-cta { display: flex; gap: 14px; flex-wrap: wrap; align-items: center; }
        .microcopy { font-size: .86rem; color: #7E8BAC; margin-top: 18px; display: flex; align-items: center; gap: 7px; }

        /* ============ The pipeline visual (signature animation) ============ */
        .stage {
            position: relative; padding: 30px 26px; border-radius: 24px;
            background: linear-gradient(160deg, rgba(255,255,255,.07), rgba(255,255,255,.022));
            border: 1px solid var(--stroke); backdrop-filter: blur(18px);
            box-shadow: 0 26px 70px rgba(0,0,0,.5);
        }
        .stage-label { font-size: .74rem; letter-spacing: .1em; text-transform: uppercase; color: #7E8BAC; margin-bottom: 20px; text-align: center; }

        .pipe { display: grid; grid-template-columns: 1fr auto 1fr; align-items: center; gap: 12px; }

        .node {
            text-align: center; padding: 20px 12px; border-radius: 17px;
            background: rgba(255,255,255,.05); border: 1px solid var(--stroke);
            transition: border-color .4s, background .4s, transform .4s;
        }
        .node.lit { border-color: rgba(99,102,241,.6); background: rgba(99,102,241,.14); transform: translateY(-3px); }
        .node-ico { width: 46px; height: 46px; margin: 0 auto 11px; display: grid; place-items: center; }
        .node-t { font-size: .88rem; font-weight: 600; }
        .node-s { font-size: .74rem; color: #7E8BAC; margin-top: 3px; }

        /* conduit with travelling packets */
        .conduit { position: relative; width: 118px; height: 46px; }
        .conduit-line {
            position: absolute; top: 50%; left: 0; right: 0; height: 2px; transform: translateY(-50%);
            background: linear-gradient(90deg, rgba(99,102,241,.15), rgba(99,102,241,.5), rgba(52,211,153,.5), rgba(52,211,153,.15));
            border-radius: 2px; overflow: hidden;
        }
        .packet {
            position: absolute; top: 50%; left: 0; width: 11px; height: 11px; border-radius: 3px;
            transform: translate(-50%,-50%); animation: travel 2.8s cubic-bezier(.45,0,.55,1) infinite;
            box-shadow: 0 0 14px currentColor;
        }
        .packet:nth-child(2) { background: var(--g-yellow); color: var(--g-yellow); animation-delay: 0s; }
        .packet:nth-child(3) { background: var(--g-green);  color: var(--g-green);  animation-delay: .93s; }
        .packet:nth-child(4) { background: var(--g-blue);   color: var(--g-blue);   animation-delay: 1.86s; }
        @keyframes travel {
            0%   { left: 0;    opacity: 0; transform: translate(-50%,-50%) scale(.5); }
            12%  { opacity: 1; transform: translate(-50%,-50%) scale(1); }
            88%  { opacity: 1; transform: translate(-50%,-50%) scale(1); }
            100% { left: 100%; opacity: 0; transform: translate(-50%,-50%) scale(.5); }
        }

        /* drive icon fills up as packets land */
        .drive-fill { animation: fill-pulse 2.8s ease-in-out infinite; transform-origin: center; }
        @keyframes fill-pulse { 0%,72% { opacity: .28; } 84% { opacity: 1; transform: scale(1.06); } 100% { opacity: .28; } }

        .stage-foot {
            margin-top: 22px; padding-top: 18px; border-top: 1px solid var(--stroke);
            display: flex; justify-content: space-between; font-size: .78rem; color: #7E8BAC;
        }
        .stage-foot b { color: #C7D2FE; font-weight: 600; }

        /* ============ Reveal on scroll ============ */
        .reveal { opacity: 0; transform: translateY(26px); transition: opacity .7s cubic-bezier(.22,1,.36,1), transform .7s cubic-bezier(.22,1,.36,1); }
        .reveal.in { opacity: 1; transform: none; }

        /* ============ Steps ============ */
        .steps { display: grid; grid-template-columns: repeat(3, 1fr); gap: 22px; counter-reset: s; }
        .step {
            position: relative; padding: 30px 26px; border-radius: var(--radius);
            background: var(--surface); border: 1px solid var(--stroke); backdrop-filter: blur(10px);
            transition: transform .3s, border-color .3s, background .3s;
        }
        .step:hover { transform: translateY(-5px); border-color: var(--stroke-2); background: var(--surface-2); }
        .step-n {
            counter-increment: s; width: 38px; height: 38px; border-radius: 11px; margin-bottom: 17px;
            display: grid; place-items: center; font-weight: 700; font-size: .95rem;
            background: linear-gradient(135deg, rgba(99,102,241,.3), rgba(37,99,235,.2));
            border: 1px solid rgba(99,102,241,.4); color: #C7D2FE;
        }
        .step-n::before { content: counter(s); }
        .step h3 { font-size: 1.1rem; margin-bottom: 9px; font-weight: 650; letter-spacing: -.01em; }
        .step p { color: var(--muted); font-size: .95rem; }

        /* ============ Stats ============ */
        .stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
        .stat {
            text-align: center; padding: 34px 20px; border-radius: var(--radius);
            background: linear-gradient(165deg, rgba(255,255,255,.07), rgba(255,255,255,.02));
            border: 1px solid var(--stroke);
        }
        .stat-v { font-size: clamp(2rem, 4vw, 2.9rem); font-weight: 800; letter-spacing: -.03em; line-height: 1;
                  background: linear-gradient(135deg, #fff, #A5B4FC); -webkit-background-clip: text; background-clip: text; color: transparent; }
        .stat-l { color: var(--muted); font-size: .9rem; margin-top: 10px; }

        /* ============ Features ============ */
        .features { display: grid; grid-template-columns: repeat(auto-fit, minmax(min(268px, 100%), 1fr)); gap: 22px; }
        .feat {
            padding: 30px 26px; border-radius: var(--radius); background: var(--surface);
            border: 1px solid var(--stroke); position: relative; overflow: hidden;
            transition: transform .3s, border-color .3s;
        }
        .feat:hover { transform: translateY(-5px); border-color: var(--stroke-2); }
        .feat::before {
            content: ''; position: absolute; inset: 0; opacity: 0; transition: opacity .35s;
            background: radial-gradient(420px circle at var(--mx, 50%) var(--my, 0%), rgba(99,102,241,.16), transparent 42%);
        }
        .feat:hover::before { opacity: 1; }
        .feat-ico {
            width: 46px; height: 46px; border-radius: 13px; display: grid; place-items: center;
            margin-bottom: 17px; background: rgba(99,102,241,.16); border: 1px solid rgba(99,102,241,.3);
        }
        .feat h3 { font-size: 1.06rem; margin-bottom: 9px; font-weight: 650; }
        .feat p { color: var(--muted); font-size: .94rem; }

        /* ============ Transfer panel (auth) ============ */
        .panel {
            border-radius: 24px; padding: 34px;
            background: linear-gradient(160deg, rgba(255,255,255,.08), rgba(255,255,255,.025));
            border: 1px solid var(--stroke-2); backdrop-filter: blur(18px);
            box-shadow: 0 26px 70px rgba(0,0,0,.45);
        }
        .panel h2 { font-size: 1.5rem; margin-bottom: 6px; }
        .panel .sub { color: var(--muted); font-size: .95rem; margin-bottom: 24px; }

        .field { margin-bottom: 16px; }
        .field label { display: block; font-size: .87rem; font-weight: 600; margin-bottom: 9px; color: #C7D2FE; }
        .field input {
            width: 100%; padding: 16px 18px; border-radius: 14px; font-size: 1rem; font-family: inherit;
            background: rgba(0,0,0,.32); border: 1px solid var(--stroke-2); color: var(--text);
            transition: border-color .25s, box-shadow .25s, background .25s;
        }
        .field input::placeholder { color: #6B7899; }
        .field input:focus {
            outline: none; border-color: var(--indigo); background: rgba(0,0,0,.45);
            box-shadow: 0 0 0 4px rgba(99,102,241,.18);
        }
        .submit-button {
            width: 100%; padding: 16px; border-radius: 14px; font-size: 1.03rem; font-weight: 650;
            background: linear-gradient(135deg, #6366F1, #2563EB); color: #fff; border: 0; cursor: pointer;
            font-family: inherit; box-shadow: 0 6px 22px rgba(79,70,229,.4); transition: transform .22s, box-shadow .22s;
        }
        .submit-button:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 12px 32px rgba(79,70,229,.55); }
        .submit-button:disabled { opacity: .6; cursor: not-allowed; }

        .meta-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; margin-bottom: 26px; }
        .meta {
            padding: 16px; border-radius: 14px; background: rgba(255,255,255,.045);
            border: 1px solid var(--stroke); text-align: center;
        }
        .meta-v { font-size: 1.5rem; font-weight: 700; color: #fff; }
        .meta-l { font-size: .78rem; color: var(--muted); margin-top: 3px; }

        .alert { padding: 15px 18px; border-radius: 13px; margin-bottom: 20px; font-size: .94rem; }
        .alert-success { background: rgba(52,168,83,.14); border: 1px solid rgba(52,168,83,.35); color: #86EFAC; }
        .alert-error   { background: rgba(234,67,53,.14); border: 1px solid rgba(234,67,53,.35); color: #FCA5A5; }
        .alert a { color: inherit; }

        /* ============ Testimonials ============ */
        .quotes { display: grid; grid-template-columns: repeat(auto-fit, minmax(min(310px, 100%), 1fr)); gap: 22px; max-width: 900px; margin: 0 auto; }
        .quote {
            position: relative; padding: 32px 30px 26px; border-radius: var(--radius);
            background: linear-gradient(165deg, rgba(255,255,255,.075), rgba(255,255,255,.022));
            border: 1px solid var(--stroke); transition: transform .3s, border-color .3s;
        }
        .quote:hover { transform: translateY(-4px); border-color: var(--stroke-2); }
        .quote-mark { color: rgba(99,102,241,.42); margin-bottom: 12px; display: block; }
        .quote blockquote { font-size: 1.09rem; line-height: 1.6; color: #E4EAFB; letter-spacing: -.005em; }
        .quote figcaption { display: flex; align-items: center; gap: 12px; margin-top: 22px; padding-top: 18px; border-top: 1px solid var(--stroke); }
        .quote .avatar {
            width: 38px; height: 38px; border-radius: 50%; flex-shrink: 0; display: grid; place-items: center;
            background: linear-gradient(135deg, #6366F1, #2563EB); color: #fff; font-weight: 700; font-size: .95rem;
        }
        .quote figcaption b { display: block; font-size: .94rem; font-weight: 620; }
        .quote figcaption small { color: var(--muted); font-size: .82rem; }

        /* ============ Trust ============ */
        .trust { display: grid; grid-template-columns: 1.1fr .9fr; gap: 46px; align-items: center; }
        .trust-list { list-style: none; display: grid; gap: 18px; }
        .trust-list li { display: flex; gap: 14px; align-items: flex-start; }
        .check {
            flex-shrink: 0; width: 25px; height: 25px; border-radius: 8px; display: grid; place-items: center;
            background: rgba(52,168,83,.18); border: 1px solid rgba(52,168,83,.38); margin-top: 2px;
        }
        .trust-list b { display: block; font-size: .99rem; margin-bottom: 3px; font-weight: 620; }
        .trust-list span { color: var(--muted); font-size: .93rem; }

        /* ============ FAQ ============ */
        .faq { max-width: 760px; margin: 0 auto; display: grid; gap: 12px; }
        .qa { border-radius: 15px; background: var(--surface); border: 1px solid var(--stroke); overflow: hidden; }
        .qa summary {
            padding: 19px 22px; cursor: pointer; font-weight: 600; font-size: 1rem; list-style: none;
            display: flex; justify-content: space-between; align-items: center; gap: 16px; transition: background .2s;
        }
        .qa summary::-webkit-details-marker { display: none; }
        .qa summary:hover { background: rgba(255,255,255,.035); }
        .qa summary::after { content: '+'; font-size: 1.45rem; color: var(--muted); font-weight: 400; transition: transform .3s; line-height: 1; }
        .qa[open] summary::after { transform: rotate(45deg); }
        .qa p { padding: 0 22px 20px; color: var(--muted); font-size: .95rem; }

        /* ============ CTA band ============ */
        .cta-band {
            text-align: center; padding: 62px 34px; border-radius: 28px;
            background: linear-gradient(135deg, rgba(99,102,241,.2), rgba(37,99,235,.1));
            border: 1px solid rgba(99,102,241,.3); position: relative; overflow: hidden;
        }
        .cta-band h2 { margin-bottom: 14px; }
        .cta-band p { color: var(--muted); max-width: 480px; margin: 0 auto 30px; }

        /* ============ Footer ============ */
        footer { border-top: 1px solid var(--stroke); padding: 56px 0 34px; margin-top: 40px; }
        .foot-grid { display: grid; grid-template-columns: 1.6fr 1fr 1fr 1fr; gap: 40px; }
        .foot-col h4 { font-size: .82rem; text-transform: uppercase; letter-spacing: .1em; color: #7E8BAC; margin-bottom: 16px; font-weight: 600; }
        .foot-col a { display: block; color: var(--muted); text-decoration: none; font-size: .92rem; margin-bottom: 10px; transition: color .2s; }
        .foot-col a:hover { color: var(--text); }
        .foot-about p { color: var(--muted); font-size: .92rem; margin: 14px 0 18px; max-width: 300px; }
        .foot-bottom {
            margin-top: 44px; padding-top: 24px; border-top: 1px solid var(--stroke);
            text-align: center; color: #6B7899; font-size: .87rem;
        }

        /* ============ Mobile ============ */
        .burger { display: none; background: none; border: 0; color: var(--text); cursor: pointer; padding: 8px; }

        @media (max-width: 940px) {
            .hero-grid, .trust { grid-template-columns: 1fr; gap: 44px; }
            .steps, .stats { grid-template-columns: 1fr; }
            .foot-grid { grid-template-columns: 1fr 1fr; gap: 32px; }
            .nav-links { display: none; }
            .burger { display: block; }
            section { padding: 68px 0; }
            .hero { padding: 48px 0 60px; }
        }
        @media (max-width: 560px) {
            .pipe { grid-template-columns: 1fr; gap: 8px; }
            .conduit { width: 46px; height: 60px; margin: 0 auto; transform: rotate(90deg); }
            .meta-row { grid-template-columns: 1fr; }
            .foot-grid { grid-template-columns: 1fr; }
            .panel { padding: 24px 20px; }
        }

        /* ============ Accessibility ============ */
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: .001ms !important; animation-iteration-count: 1 !important;
                transition-duration: .001ms !important; scroll-behavior: auto !important;
            }
            .reveal { opacity: 1; transform: none; }
        }
        :focus-visible { outline: 2px solid var(--indigo); outline-offset: 3px; border-radius: 6px; }
    </style>
</head>
<body>

<div class="backdrop">
    <div class="aurora a"></div>
    <div class="aurora b"></div>
    <div class="aurora c"></div>
    <div class="grid-overlay"></div>
</div>

{{-- ============ NAV ============ --}}
<nav class="nav" id="nav">
    <div class="wrap nav-in">
        <a href="{{ route('home.v2') }}" class="brand">
            @include('partials.logo-mark', ['size' => 36, 'animated' => true])
            <span class="brand-name">Weto<span>Drive</span></span>
        </a>

        <div class="nav-links">
            <a href="#how">How it works</a>
            <a href="#why">Why it's safe</a>
            <a href="#faq">FAQ</a>
            <a href="{{ route('subscription.pricing') }}">Pricing</a>
        </div>

        <div style="display:flex; align-items:center; gap:12px;">
            @auth
                <a href="{{ route('subscription.manage') }}" class="btn btn-ghost" style="padding:10px 18px;">Dashboard</a>
            @else
                <a href="{{ route('auth.google') }}" class="btn btn-primary" style="padding:11px 20px;">Get started</a>
            @endauth
        </div>
    </div>
</nav>

{{-- ============ HERO ============ --}}
<header class="hero">
    <div class="wrap hero-grid">
        <div>
            <div class="pill">
                <span class="dot"></span>
                No downloads. No re-uploads. No storage on your device.
            </div>

            <h1><span class="nw">Your WeTransfer link,</span><br><span class="grad">straight into Drive.</span></h1>

            {{-- First sentence is the live homepage's tagline, word for word. It is the
                 page's main keyword string and the thing the description tag echoes. --}}
            <p class="lede">
                Transfer files from WeTransfer to Google Drive instantly. Paste the link and the
                files stream straight into your Drive, with no downloading and uploading and no
                storage used on your device.
            </p>

            <div class="hero-cta">
                @auth
                    <a href="#transfer" class="btn btn-primary btn-lg">Start a transfer</a>
                    <a href="{{ route('subscription.pricing') }}" class="btn btn-ghost btn-lg">See plans</a>
                @else
                    <a href="{{ route('auth.google') }}" class="btn btn-primary btn-lg">
                        <svg width="19" height="19" viewBox="0 0 24 24" aria-hidden="true">
                            <path fill="currentColor" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                            <path fill="currentColor" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                            <path fill="currentColor" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                            <path fill="currentColor" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                        </svg>
                        Get Started with Google Drive
                    </a>
                    <a href="#how" class="btn btn-ghost btn-lg">See how it works</a>
                @endauth
            </div>

            <div class="microcopy">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#34A853" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                </svg>
                We can add files to your Drive, and nothing else. Your first transfer is free.
            </div>
        </div>

        {{-- Signature animation: the pipeline --}}
        <div class="stage" aria-hidden="true">
            <div class="stage-label">Live</div>
            <div class="pipe">
                <div class="node" id="nodeFrom">
                    <div class="node-ico">
                        <svg width="42" height="42" viewBox="0 0 48 48" fill="none">
                            <rect x="8" y="14" width="32" height="24" rx="5" fill="rgba(255,255,255,.1)" stroke="rgba(255,255,255,.4)" stroke-width="2"/>
                            <path d="M14 22h20M14 28h13" stroke="rgba(255,255,255,.55)" stroke-width="2.4" stroke-linecap="round"/>
                            <circle cx="34" cy="14" r="7" fill="#6366F1"/>
                            <path d="M31 14h6M34 11v6" stroke="#fff" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                    </div>
                    <div class="node-t">WeTransfer link</div>
                    <div class="node-s">Pasted by you</div>
                </div>

                <div class="conduit">
                    <div class="conduit-line"></div>
                    <div class="packet"></div>
                    <div class="packet"></div>
                    <div class="packet"></div>
                </div>

                <div class="node" id="nodeTo">
                    <div class="node-ico">
                        {{-- The real Google Drive mark: three folded panels, not a plain triangle.
                             Nominative use, same as the Google G on the sign-in button. --}}
                        <svg class="drive-fill" width="42" height="38" viewBox="0 0 87.3 78" fill="none">
                            <path d="m6.6 66.85 3.85 6.65c.8 1.4 1.95 2.5 3.3 3.3l13.75-23.8h-27.5c0 1.55.4 3.1 1.2 4.5z" fill="#0066da"/>
                            <path d="m43.65 25-13.75-23.8c-1.35.8-2.5 1.9-3.3 3.3l-25.4 44a9.06 9.06 0 0 0-1.2 4.5h27.5z" fill="#00ac47"/>
                            <path d="m73.55 76.8c1.35-.8 2.5-1.9 3.3-3.3l1.6-2.75 7.65-13.25c.8-1.4 1.2-2.95 1.2-4.5h-27.502l5.852 11.5z" fill="#ea4335"/>
                            <path d="m43.65 25 13.75-23.8c-1.35-.8-2.9-1.2-4.5-1.2h-18.5c-1.6 0-3.15.45-4.5 1.2z" fill="#00832d"/>
                            <path d="m59.8 53h-32.3l-13.75 23.8c1.35.8 2.9 1.2 4.5 1.2h50.8c1.6 0 3.15-.45 4.5-1.2z" fill="#2684fc"/>
                            <path d="m73.4 26.5-12.7-22c-.8-1.4-1.95-2.5-3.3-3.3l-13.75 23.8 16.15 28h27.45c0-1.55-.4-3.1-1.2-4.5z" fill="#ffba00"/>
                        </svg>
                    </div>
                    <div class="node-t">Google Drive</div>
                    <div class="node-s">Yours, instantly</div>
                </div>
            </div>

            <div class="stage-foot">
                <span>Streamed, never stored <b>·</b> 0 bytes on your device</span>
                @if ($stats['bytes'] > 1073741824)
                    <span>over <b>{{ number_format(floor($stats['bytes'] / 1073741824)) }}</b> GB moved</span>
                @endif
            </div>
        </div>
    </div>
</header>

{{-- ============ TRANSFER PANEL (auth only) ============ --}}
@auth
<section id="transfer" style="padding-top: 20px;">
    <div class="wrap" style="max-width: 760px;">
        <div class="panel reveal">
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

            <h2>Welcome back, {{ Auth::user()->name }}</h2>
            <p class="sub">Paste a WeTransfer link and it lands in your Drive.</p>

            <div class="meta-row">
                <div class="meta">
                    <div class="meta-v">{{ ucfirst(Auth::user()->subscription_tier) }}</div>
                    <div class="meta-l">Current plan</div>
                </div>
                <div class="meta">
                    <div class="meta-v" data-transfers-remaining>
                        @if(Auth::user()->hasActiveSubscription())
                            @php $subscription = Auth::user()->activeSubscription; @endphp
                            {{ $subscription->getRemainingTransfers() === null ? '∞' : $subscription->getRemainingTransfers() }}
                        @else
                            {{ 5 - Auth::user()->total_transfers }}
                        @endif
                    </div>
                    <div class="meta-l">Transfers left</div>
                </div>
                <div class="meta">
                    <div class="meta-v" data-total-transfers>{{ Auth::user()->total_transfers }}</div>
                    <div class="meta-l">Total transfers</div>
                </div>
            </div>

            @if(Auth::user()->hasTrialTransferAvailable())
                <div class="alert alert-success">Your first transfer supports files up to 3GB.</div>
            @endif

            {{-- ids below are the contract with partials/transfer-script --}}
            <div id="transferFormContainer">
                <form id="transferForm" method="POST" action="{{ route('transfer') }}">
                    @csrf
                    <div class="field">
                        <label for="wetransfer_url">WeTransfer URL</label>
                        <input type="url" id="wetransfer_url" name="wetransfer_url" required
                               value="{{ old('wetransfer_url') }}"
                               placeholder="https://wetransfer.com/downloads/... or https://we.tl/t-...">
                    </div>
                    <button type="submit" class="submit-button" id="transferButton">Transfer to Google Drive</button>
                </form>
            </div>

            <div id="progressContainer" style="display:none;">
                <div style="text-align:center; margin-bottom:18px;">
                    <div style="font-size:1.05rem; font-weight:600;" id="progressStatus">Initializing transfer...</div>
                    <div style="color:var(--muted); font-size:.92rem; margin-top:5px;" id="progressFilename"></div>
                </div>

                <div style="background:rgba(0,0,0,.4); border-radius:999px; height:26px; overflow:hidden; border:1px solid var(--stroke);">
                    <div id="progressBar" style="background:linear-gradient(90deg,#6366F1,#2563EB,#34D399); height:100%; width:0%; transition:width .35s ease; display:flex; align-items:center; justify-content:center; position:relative;">
                        <span id="progressPercent" style="color:#fff; font-weight:650; font-size:.85rem; position:absolute;">0%</span>
                    </div>
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px; margin:18px 0;">
                    <div class="meta">
                        <div class="meta-v" style="font-size:1.15rem;" id="bytesTransferred">0 MB</div>
                        <div class="meta-l">Transferred</div>
                    </div>
                    <div class="meta">
                        <div class="meta-v" style="font-size:1.15rem;" id="totalSize">0 MB</div>
                        <div class="meta-l">Total size</div>
                    </div>
                </div>

                <div id="statusMessage" style="text-align:center; padding:14px; background:rgba(99,102,241,.14); border:1px solid rgba(99,102,241,.3); border-radius:12px; color:#C7D2FE; font-size:.93rem;">
                    Transfer in progress. You can keep this tab open.
                </div>
                <div id="completionMessage" style="display:none; margin-top:18px;"></div>
            </div>
        </div>
    </div>
</section>
@endauth

{{-- ============ HOW IT WORKS ============ --}}
<section id="how">
    <div class="wrap">
        <div class="section-head reveal">
            <div class="eyebrow">How it works</div>
            <h2>Three steps, then forget about it</h2>
            <p>The whole point is that you stop babysitting a download bar.</p>
        </div>

        <div class="steps">
            <div class="step reveal">
                <div class="step-n"></div>
                <h3>Connect your Drive</h3>
                <p>Sign in with Google once. We ask only for permission to add files to your Drive, nothing else.</p>
            </div>
            <div class="step reveal" style="transition-delay:.1s">
                <div class="step-n"></div>
                <h3>Paste the link</h3>
                <p>Any WeTransfer link works, both the short <code style="color:#A5B4FC">we.tl</code> ones and full download URLs.</p>
            </div>
            <div class="step reveal" style="transition-delay:.2s">
                <div class="step-n"></div>
                <h3>Close the tab</h3>
                <p>We stream it across server to server and email you the moment it has landed in your Drive.</p>
            </div>
        </div>
    </div>
</section>

{{-- ============ STATS (real numbers, straight from the db) ============ --}}
@if ($stats['transfers'] > 0)
<section style="padding-top:0;">
    <div class="wrap">
        <div class="stats">
            {{-- Labels say exactly what each number counts. "Accounts connected" is not
                 "active users", and the GB total is a floor, hence "over". --}}
            <div class="stat reveal">
                <div class="stat-v" data-count="{{ $stats['accounts'] }}">0</div>
                <div class="stat-l">Google accounts connected</div>
            </div>
            <div class="stat reveal" style="transition-delay:.1s">
                <div class="stat-v" data-count="{{ $stats['transfers'] }}">0</div>
                <div class="stat-l">Transfers delivered to Drive</div>
            </div>
            <div class="stat reveal" style="transition-delay:.2s">
                <div class="stat-v" data-count="{{ (int) floor($stats['bytes'] / 1073741824) }}" data-prefix="over " data-suffix=" GB">0</div>
                <div class="stat-l">Moved from WeTransfer to Google Drive</div>
            </div>
        </div>
    </div>
</section>
@endif

{{-- ============ FEATURES ============ --}}
<section>
    <div class="wrap">
        <div class="section-head reveal">
            <div class="eyebrow">What you get</div>
            <h2>Built for the files that break everything else</h2>
        </div>

        <div class="features">
            <div class="feat reveal">
                <div class="feat-ico">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#A5B4FC" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2 3 14h9l-1 8 10-12h-9l1-8z"/></svg>
                </div>
                <h3>Instant Transfer</h3>
                <p>No more manual downloading and uploading. Transfer files directly from WeTransfer to Google Drive, at sizes up to 500GB.</p>
            </div>
            <div class="feat reveal" style="transition-delay:.06s">
                <div class="feat-ico">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#A5B4FC" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-6.2-8.6"/><path d="M22 4 12 14.01l-3-3"/></svg>
                </div>
                <h3>Save Storage</h3>
                <p>Files stream directly to your Google Drive without taking up space on your device. Great for a full SSD, a slow connection, or a phone.</p>
            </div>
            <div class="feat reveal" style="transition-delay:.12s">
                <div class="feat-ico">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#A5B4FC" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
                </div>
                <h3>Resumable by default</h3>
                <p>Big transfers upload in chunks, so a wobble in the connection does not cost you the whole file.</p>
            </div>
            <div class="feat reveal" style="transition-delay:.18s">
                <div class="feat-ico">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#A5B4FC" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/><path d="M4 22v-7"/></svg>
                </div>
                <h3>Told when it's done</h3>
                <p>An email with the Drive link the second it lands, so you never sit and watch a bar.</p>
            </div>
            <div class="feat reveal" style="transition-delay:.24s">
                <div class="feat-ico">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#A5B4FC" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                </div>
                <h3>Before the link expires</h3>
                <p>WeTransfer links die after a week. Move it to Drive once and it is yours to keep.</p>
            </div>
            <div class="feat reveal" style="transition-delay:.3s">
                <div class="feat-ico">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#A5B4FC" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                </div>
                <h3>Fast &amp; Secure</h3>
                <p>Powered by Google's secure infrastructure with enterprise-grade encryption. Reply to any email from us and it reaches the person who built this.</p>
            </div>
        </div>
    </div>
</section>

{{-- ============ TESTIMONIALS ============ --}}
{{-- Real replies to our check-in email. Attribution is deliberately thin: first
     name + initial at most, never the email address, since these were private
     replies rather than submitted public reviews. --}}
<section id="words" style="padding-top:0;">
    <div class="wrap">
        <div class="section-head reveal">
            <div class="eyebrow">In their words</div>
            <h2>What people write back</h2>
            <p>We email everyone once to ask how it went. These are some of the replies.</p>
        </div>

        <div class="quotes">
            @foreach ([
                ['quote' => 'It was perfect, thank you for building this.', 'who' => 'W', 'name' => 'WetoDrive user', 'ctx' => 'Replied to our check-in email'],
                ['quote' => 'All was very nice and easy to understand. It has done what I wanted it to. Thank you.', 'who' => 'J', 'name' => 'Jawad T.', 'ctx' => 'Water &amp; development'],
            ] as $i => $t)
                <figure class="quote reveal" style="transition-delay:{{ $i * .09 }}s">
                    <svg class="quote-mark" width="30" height="30" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <path d="M7.6 6C5 6 3 8.1 3 10.7c0 2.5 1.9 4.5 4.3 4.5.4 0 .8 0 1.1-.2-.5 1.6-2 2.9-3.6 3.3l.6 1.7c3.6-.9 6.4-4.3 6.4-8.5C11.8 8.1 10 6 7.6 6zm10 0c-2.6 0-4.6 2.1-4.6 4.7 0 2.5 1.9 4.5 4.3 4.5.4 0 .8 0 1.1-.2-.5 1.6-2 2.9-3.6 3.3l.6 1.7c3.6-.9 6.4-4.3 6.4-8.5C21.8 8.1 20 6 17.6 6z"/>
                    </svg>
                    <blockquote>{!! $t['quote'] !!}</blockquote>
                    <figcaption>
                        <span class="avatar">{{ $t['who'] }}</span>
                        <span>
                            <b>{{ $t['name'] }}</b>
                            <small>{!! $t['ctx'] !!}</small>
                        </span>
                    </figcaption>
                </figure>
            @endforeach
        </div>
    </div>
</section>

{{-- ============ TRUST ============ --}}
<section id="why">
    <div class="wrap trust">
        <div class="reveal">
            <div class="eyebrow">Why it's safe</div>
            <h2 style="margin-bottom:20px;">We ask for less than you'd expect</h2>
            <ul class="trust-list">
                <li>
                    <span class="check"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#34A853" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></span>
                    <div><b>We cannot read your Drive</b><span>The Google permission we request lets us add files. It does not let us browse, open, or delete what is already there.</span></div>
                </li>
                <li>
                    <span class="check"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#34A853" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></span>
                    <div><b>Your files are not kept</b><span>Files stream through and the temporary copy is deleted as soon as the upload finishes. We are a pipe, not a warehouse.</span></div>
                </li>
                <li>
                    <span class="check"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#34A853" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></span>
                    <div><b>Disconnect whenever you like</b><span>One button removes our access and wipes the stored token. You can also revoke it from your Google account page.</span></div>
                </li>
                <li>
                    <span class="check"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#34A853" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></span>
                    <div><b>Cancel in one click</b><span>No phone call, no retention script. Cancel from your dashboard and you keep access until the period you paid for runs out.</span></div>
                </li>
            </ul>
        </div>

        <div class="stage reveal" style="padding:28px;">
            <div class="stage-label">Permissions we request</div>
            <div style="display:grid; gap:12px;">
                <div style="display:flex; align-items:center; gap:13px; padding:15px; border-radius:13px; background:rgba(52,168,83,.1); border:1px solid rgba(52,168,83,.3);">
                    <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="#34A853" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                    <div><div style="font-weight:620; font-size:.93rem;">Add files to your Drive</div><div style="color:var(--muted); font-size:.82rem;">drive.file scope</div></div>
                </div>
                @foreach ([
                    'Read your existing files',
                    'Delete or modify anything',
                    'See your Drive contents',
                    'Share anything on your behalf',
                ] as $denied)
                    <div style="display:flex; align-items:center; gap:13px; padding:15px; border-radius:13px; background:rgba(255,255,255,.03); border:1px solid var(--stroke);">
                        <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="#6B7899" stroke-width="2.6" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
                        <div style="color:var(--muted); font-size:.93rem;">{{ $denied }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</section>

{{-- ============ FAQ ============ --}}
<section id="faq">
    <div class="wrap">
        <div class="section-head reveal">
            <div class="eyebrow">Questions</div>
            <h2>The things people ask first</h2>
        </div>

        <div class="faq">
            @foreach ([
                ['Do I need a WeTransfer account?', 'No. You only need the link somebody sent you. We handle the rest.'],
                ['What happens if the transfer fails halfway?', 'Large files upload in chunks and resume automatically. If it still fails, it does not count against your quota and we email you what went wrong.'],
                ['How big a file can I move?', 'The free plan covers small files, and your very first transfer stretches to 3GB. Pro handles up to 25GB and Premium goes to 500GB.'],
                ['Do you keep a copy of my files?', 'No. The file streams through to your Drive and the temporary copy is deleted the moment the upload completes.'],
                ['Can I cancel any time?', 'Yes, in one click from your dashboard. You keep everything you paid for until the end of the current period.'],
            ] as $i => $qa)
                <details class="qa reveal" @if($i === 0) open @endif style="transition-delay:{{ $i * .05 }}s">
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
        <div class="cta-band reveal">
            <h2>That link is going to expire</h2>
            <p>Move it into your Drive before it does. The first one is on us.</p>
            @auth
                <a href="#transfer" class="btn btn-primary btn-lg">Start a transfer</a>
            @else
                <a href="{{ route('auth.google') }}" class="btn btn-primary btn-lg">Continue with Google</a>
            @endauth
        </div>
    </div>
</section>

{{-- ============ FOOTER ============ --}}
<footer>
    <div class="wrap">
        <div class="foot-grid">
            <div class="foot-col foot-about">
                <a href="{{ route('home.v2') }}" class="brand">
                    @include('partials.logo-mark', ['size' => 32])
                    <span class="brand-name">Weto<span>Drive</span></span>
                </a>
                <p>WeTransfer links, delivered straight into Google Drive. No downloads, no re-uploads, no space used on your device.</p>
                <a href="https://www.producthunt.com/products/wetodrive?embed=true&utm_source=badge-featured" target="_blank" rel="noopener">
                    <img src="https://api.producthunt.com/widgets/embed-image/v1/featured.svg?post_id=1029974&theme=dark&t=1761306053608"
                         alt="WetoDrive on Product Hunt" width="220" height="47" style="max-width:100%;">
                </a>
            </div>

            {{-- Anchor text below is copied verbatim from the live homepage footer.
                 These are the only internal links the SEO landing pages get from the
                 homepage, so both the targets and the anchor wording are load-bearing. --}}
            <div class="foot-col">
                <h4>Quick Links</h4>
                <a href="{{ route('home') }}">Home</a>
                <a href="{{ route('subscription.pricing') }}">Pricing</a>
                @auth <a href="{{ route('subscription.manage') }}">Dashboard</a>
                @else <a href="{{ route('auth.google') }}">Sign In</a> @endauth
            </div>

            <div class="foot-col">
                <h4>WeTransfer Guides</h4>
                <a href="{{ route('seo.pricing') }}">WeTransfer Pricing</a>
                <a href="{{ route('seo.send-files') }}">How to Send Files</a>
                <a href="{{ route('seo.upload') }}">Upload Tutorial</a>
                <a href="{{ route('seo.free') }}">Free Plan Guide</a>
                <a href="{{ route('seo.alternative') }}">WeTransfer Alternative</a>
                <a href="{{ route('seo.google-drive-guide') }}">Save to Google Drive</a>
            </div>

            <div class="foot-col">
                <h4>Support</h4>
                <a href="{{ route('support.help') }}">Help Center</a>
                <a href="{{ route('support.contact') }}">Contact Us</a>
                <a href="{{ route('support.privacy') }}">Privacy Policy</a>
                <a href="{{ route('support.terms') }}">Terms of Service</a>
            </div>
        </div>

        <div class="foot-bottom">
            &copy; {{ date('Y') }} WetoDrive. Built for people with files too big to babysit.
        </div>
    </div>
</footer>

<script>
    // Nav shadow on scroll
    const nav = document.getElementById('nav');
    addEventListener('scroll', () => nav.classList.toggle('scrolled', scrollY > 10), { passive: true });

    // Reveal on scroll
    const io = new IntersectionObserver((entries) => {
        entries.forEach(e => {
            if (e.isIntersecting) { e.target.classList.add('in'); io.unobserve(e.target); }
        });
    }, { threshold: .12, rootMargin: '0px 0px -60px 0px' });
    document.querySelectorAll('.reveal').forEach(el => io.observe(el));

    // Count-up stats, once, when they scroll into view
    const reduced = matchMedia('(prefers-reduced-motion: reduce)').matches;
    const counters = new IntersectionObserver((entries) => {
        entries.forEach(e => {
            if (!e.isIntersecting) return;
            counters.unobserve(e.target);
            const el = e.target;
            const target = +el.dataset.count;
            const suffix = el.dataset.suffix || '';
            const prefix = el.dataset.prefix || '';
            if (reduced) { el.textContent = prefix + target.toLocaleString() + suffix; return; }
            const dur = 1400, t0 = performance.now();
            const tick = (now) => {
                const p = Math.min((now - t0) / dur, 1);
                const eased = 1 - Math.pow(1 - p, 3);
                el.textContent = prefix + Math.round(target * eased).toLocaleString() + suffix;
                if (p < 1) requestAnimationFrame(tick);
            };
            requestAnimationFrame(tick);
        });
    }, { threshold: .5 });
    document.querySelectorAll('[data-count]').forEach(el => counters.observe(el));

    // Pipeline: light up each node as the packets arrive, so the loop reads as a real handoff
    if (!reduced) {
        const from = document.getElementById('nodeFrom');
        const to = document.getElementById('nodeTo');
        setInterval(() => {
            from.classList.add('lit');
            setTimeout(() => from.classList.remove('lit'), 700);
            setTimeout(() => {
                to.classList.add('lit');
                setTimeout(() => to.classList.remove('lit'), 700);
            }, 1400);
        }, 2800);
    }

    // Spotlight that follows the cursor across feature cards
    document.querySelectorAll('.feat').forEach(card => {
        card.addEventListener('pointermove', (e) => {
            const r = card.getBoundingClientRect();
            card.style.setProperty('--mx', (e.clientX - r.left) + 'px');
            card.style.setProperty('--my', (e.clientY - r.top) + 'px');
        });
    });

    @include('partials.transfer-script')
</script>

</body>
</html>
