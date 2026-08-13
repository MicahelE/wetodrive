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
        <path class="lm-c1" d="M5 25.5 L11.5 32 L5 38.5"  stroke="#FBBC04" opacity=".55"/>
        <path class="lm-c2" d="M14 25.5 L20.5 32 L14 38.5" stroke="#34A853" opacity=".85"/>
    </g>

    {{-- Three folded panels rather than one flat triangle, so the mark reads as a
         drive with depth. Same construction logic as Drive's, our own palette. --}}
    <g stroke-linejoin="round">
        <path d="M42 13 L28.6 43 H42 Z"                  fill="#FFFFFF"/>
        <path d="M42 13 L42 43 H55.4 Z"                  fill="#BFD6FF"/>
        <path d="M28.6 43 H55.4 L58.4 49.6 a1.8 1.8 0 0 1-1.6 2.6 H27.2 a1.8 1.8 0 0 1-1.6-2.6 Z" fill="#7FA9FF"/>
    </g>
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
