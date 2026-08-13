{{--
    WetoDrive mark: two chevrons in Drive's palette flying into a Drive triangle.
    Reads as "your transfer, arriving". Pass $size (px) and $animated (bool).

    Ids are suffixed with a uniqid because a page can render this more than once
    and duplicate gradient ids would make the second copy render with the first's fill.
--}}
@php
    $size = $size ?? 40;
    $animated = $animated ?? false;
    $uid = 'lm' . substr(md5(uniqid('', true)), 0, 6);
@endphp

<svg width="{{ $size }}" height="{{ $size }}" viewBox="0 0 64 64" fill="none"
     class="{{ $animated ? 'logo-anim' : '' }}" role="img" aria-label="WetoDrive">
    <defs>
        <linearGradient id="{{ $uid }}-b" x1="0" y1="0" x2="1" y2="1">
            <stop offset="0%" stop-color="#4F46E5"/>
            <stop offset="100%" stop-color="#2563EB"/>
        </linearGradient>
        <linearGradient id="{{ $uid }}-t" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0%" stop-color="#FFFFFF"/>
            <stop offset="100%" stop-color="#DBEAFE"/>
        </linearGradient>
    </defs>

    <rect width="64" height="64" rx="16" fill="url(#{{ $uid }}-b)"/>

    <g fill="none" stroke-width="3.4" stroke-linecap="round" stroke-linejoin="round">
        <path class="lm-c1" d="M6 25.5 L12.5 32 L6 38.5"  stroke="#FBBC04" opacity=".55"/>
        <path class="lm-c2" d="M15 25.5 L21.5 32 L15 38.5" stroke="#34A853" opacity=".8"/>
    </g>

    <path d="M43 15.5 L60 47 a2.6 2.6 0 0 1-2.3 3.6 H28.3 a2.6 2.6 0 0 1-2.3-3.6 Z" fill="url(#{{ $uid }}-t)"/>
    <rect x="33" y="40.5" width="19" height="3.4" rx="1.7" fill="#2563EB" opacity=".45"/>
</svg>

@once
<style>
    /* The chevrons drift toward the Drive, so the mark animates the verb. */
    .logo-anim .lm-c1 { animation: lm-fly 2.6s ease-in-out infinite; }
    .logo-anim .lm-c2 { animation: lm-fly 2.6s ease-in-out infinite .28s; }
    @keyframes lm-fly {
        0%, 100% { transform: translateX(0);   opacity: .35; }
        45%      { transform: translateX(5px); opacity: 1; }
    }
    @media (prefers-reduced-motion: reduce) {
        .logo-anim .lm-c1, .logo-anim .lm-c2 { animation: none; }
    }
</style>
@endonce
