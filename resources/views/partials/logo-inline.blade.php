{{--
    The mark, sized and baselined to sit exactly where the old 📦 emoji sat.

    Every page outside the homepage had its own bespoke .logo CSS, so this is
    built to drop straight in as a text glyph: em-sized, inline-block, nudged
    onto the baseline. That way the brand updates everywhere without touching
    thirteen separate stylesheets.

    Ids are suffixed because a page renders this more than once and duplicate
    gradient ids make later copies inherit the first one's fill.
--}}
@php $iid = 'li' . substr(md5(uniqid('', true)), 0, 6); @endphp
<svg viewBox="0 0 64 64" fill="none" role="img" aria-label="WetoDrive"
     style="width:1.05em;height:1.05em;display:inline-block;vertical-align:-.19em;flex-shrink:0;">
    <defs>
        <linearGradient id="{{ $iid }}-b" x1="0" y1="0" x2="1" y2="1">
            <stop offset="0%" stop-color="#4F46E5"/><stop offset="100%" stop-color="#2563EB"/>
        </linearGradient>
        <linearGradient id="{{ $iid }}-w" x1=".2" y1="0" x2=".8" y2="1">
            <stop offset="0%" stop-color="#FFFFFF"/><stop offset="100%" stop-color="#E4ECFF"/>
        </linearGradient>
    </defs>
    <rect width="64" height="64" rx="15" fill="url(#{{ $iid }}-b)"/>
    <g stroke="#BFD6FF" stroke-width="2.6" stroke-linecap="round">
        <path d="M8 36 L16 32" opacity=".30"/>
        <path d="M9 45 L20 39" opacity=".45"/>
        <path d="M15 53 L25 47" opacity=".28"/>
    </g>
    <path d="M56 8 L11 27 L29 33 Z" fill="url(#{{ $iid }}-w)"/>
    <path d="M56 8 L29 33 L31 57 Z" fill="#A9C6FF"/>
    <path d="M29 33 L31 57 L40 43 Z" fill="#6C93E8"/>
</svg>
