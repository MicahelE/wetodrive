{{--
    WetoDrive mark: a folded paper plane that is still a triangle.

    The plane says the verb (send) and the triangle nods to the destination, so
    the mark carries both halves of the name. It is tilted off-axis and trailed
    by speed lines because the two previous attempts failed for the same reason:
    a centred, symmetrical triangle is static, and static reads as plain.

    Three facets, lit as if folded from paper. Pass $size (px). Gradient ids are
    suffixed because a page renders this more than once, and duplicate ids make
    later copies inherit the first one's fill.
--}}
@php
    $size = $size ?? 40;
    $uid = 'lm' . substr(md5(uniqid('', true)), 0, 6);
@endphp

<svg width="{{ $size }}" height="{{ $size }}" viewBox="0 0 64 64" fill="none"
     role="img" aria-label="WetoDrive" style="display:block; flex-shrink:0;">
    <defs>
        <linearGradient id="{{ $uid }}-b" x1="0" y1="0" x2="1" y2="1">
            <stop offset="0%" stop-color="#4F46E5"/>
            <stop offset="100%" stop-color="#2563EB"/>
        </linearGradient>
        <linearGradient id="{{ $uid }}-w" x1=".2" y1="0" x2=".8" y2="1">
            <stop offset="0%" stop-color="#FFFFFF"/>
            <stop offset="100%" stop-color="#E4ECFF"/>
        </linearGradient>
    </defs>

    <rect width="64" height="64" rx="15" fill="url(#{{ $uid }}-b)"/>

    {{-- Trails: parallel to the flight path, fading back --}}
    <g stroke="#BFD6FF" stroke-width="2.6" stroke-linecap="round">
        <path d="M8 36 L16 32" opacity=".30"/>
        <path d="M9 45 L20 39" opacity=".45"/>
        <path d="M15 53 L25 47" opacity=".28"/>
    </g>

    {{-- Upper wing, catching the light --}}
    <path d="M56 8 L11 27 L29 33 Z" fill="url(#{{ $uid }}-w)"/>
    {{-- Keel, seen edge on --}}
    <path d="M56 8 L29 33 L31 57 Z" fill="#A9C6FF"/>
    {{-- Shadowed inner fold, which is what makes it read as folded rather than flat --}}
    <path d="M29 33 L31 57 L40 43 Z" fill="#6C93E8"/>
</svg>
