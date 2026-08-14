{{--
    WetoDrive mark.

    One shape, one idea: the Drive triangle with a descending arrow cut clean out
    of it, so the badge gradient shows through. Reads as "files drop into Drive"
    at 128px and still resolves at 16px, which the previous chevrons-plus-triangle
    version did not — it was two competing shapes crammed into one badge.

    Pass $size (px). Ids are suffixed because a page renders this more than once
    and duplicate gradient ids make later copies pick up the first one's fill.
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
        <linearGradient id="{{ $uid }}-t" x1=".5" y1="0" x2=".5" y2="1">
            <stop offset="0%" stop-color="#FFFFFF"/>
            <stop offset="100%" stop-color="#CFE0FF"/>
        </linearGradient>
    </defs>

    <rect width="64" height="64" rx="15" fill="url(#{{ $uid }}-b)"/>

    {{-- evenodd turns the arrow subpath into a hole rather than a second shape --}}
    <path fill-rule="evenodd" clip-rule="evenodd" fill="url(#{{ $uid }}-t)"
          d="M32 14 L53.6 50.2 a1.6 1.6 0 0 1-1.4 2.4 H11.8 a1.6 1.6 0 0 1-1.4-2.4 Z
             M28.5 25.5 h7 v9 h5.1 L32 45 l-8.6-10.5 H28.5 Z"/>
</svg>
