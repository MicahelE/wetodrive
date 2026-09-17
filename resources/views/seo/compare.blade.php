@php
    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'FAQPage',
        'mainEntity' => array_map(fn ($qa) => [
            '@type' => 'Question',
            'name' => $qa[0],
            'acceptedAnswer' => ['@type' => 'Answer', 'text' => $qa[1]],
        ], $page['faqs']),
    ];
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $page['title'] }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="{{ $page['description'] }}">
    <meta name="keywords" content="{{ $page['keywords'] }}">
    <link rel="canonical" href="{{ $page['canonical'] }}">

    <meta property="og:title" content="{{ $page['title'] }}">
    <meta property="og:description" content="{{ $page['description'] }}">
    <meta property="og:url" content="{{ $page['canonical'] }}">
    <meta property="og:type" content="article">
    <meta property="og:site_name" content="WetoDrive">
    <meta property="og:image" content="{{ asset('logo.svg') }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $page['title'] }}">
    <meta name="twitter:description" content="{{ $page['description'] }}">

    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="apple-touch-icon" href="{{ asset('favicon.svg') }}">
    <meta name="theme-color" content="#2A42F7">

    {{-- Built from the same FAQ list the page shows, so the two cannot drift. --}}
    <script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>

    @include('partials.site-analytics')
    @include('partials.site-styles')
    <style>
        .answer {
            background: var(--white); color: var(--ink); text-align: left;
            border: 1px solid var(--line); border-left: 4px solid var(--blue);
            border-radius: var(--r-sm); padding: 18px 20px; max-width: 760px; margin: 0 auto;
        }
        .answer b { display: block; margin-bottom: 6px; }
        .compare { background: var(--white); border: 1px solid var(--line); border-radius: var(--r); overflow-x: auto; }
        .compare table { width: 100%; border-collapse: collapse; min-width: 560px; font-size: .93rem; }
        .compare th, .compare td { text-align: left; padding: 13px 15px; border-bottom: 1px solid var(--line); vertical-align: top; }
        .compare thead th { background: var(--blue-soft); font-weight: 650; }
        .compare tbody th { font-weight: 600; width: 24%; }
        .compare tbody tr:last-child th, .compare tbody tr:last-child td { border-bottom: 0; }
        .uses { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; }
        .uses ul { list-style: none; display: grid; gap: 12px; margin-top: 12px; }
        .uses li { display: flex; gap: 10px; color: var(--muted); font-size: .95rem; }
        .checked { text-align: center; color: var(--muted); font-size: .85rem; margin-top: 12px; }
        .sources { font-size: .86rem; color: var(--muted); max-width: 720px; margin: 0 auto; }
        .sources a { color: inherit; }
        @media (max-width: 860px) { .uses { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

<a href="#compare" class="skip">Skip to the comparison</a>

@include('partials.site-nav')

<header class="hero">
    <div class="wrap">
        <h1>{{ $page['h1'] }}</h1>
        <p class="tagline">{{ $page['tagline'] }}</p>
        <div class="answer">
            <b>The short answer</b>
            {{ $page['short_answer'] }}
        </div>
    </div>
</header>

{{-- ============ TABLE ============ --}}
<section id="compare">
    <div class="wrap">
        <div class="head">
            <h2>{{ $page['h1'] }} at a glance</h2>
            <p>Free limits, file sizes, how long files last and what you pay.</p>
        </div>
        <div class="compare">
            <table>
                <thead>
                    <tr>
                        <th scope="col"></th>
                        <th scope="col">WeTransfer</th>
                        <th scope="col">{{ $page['other'] }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($page['rows'] as $row)
                        <tr>
                            <th scope="row">{{ $row[0] }}</th>
                            <td>{{ $row[1] }}</td>
                            <td>{{ $row[2] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <p class="checked">Prices and limits checked {{ \App\Support\ComparisonPages::CHECKED }}. Plans change, so check each provider before you buy.</p>
    </div>
</section>

{{-- ============ WHEN TO USE ============ --}}
<section id="why" class="rise" style="padding-top:0;">
    <div class="wrap">
        <div class="head"><h2>When to use each</h2></div>
        <div class="uses">
            @foreach ([['WeTransfer', $page['use_wetransfer']], [$page['other'], $page['use_other']]] as [$name, $reasons])
                <div class="card">
                    <h3>Choose {{ $name }} when</h3>
                    <ul>
                        @foreach ($reasons as $reason)
                            <li>
                                <svg class="tick" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                                <span>{{ $reason }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ============ USE BOTH ============ --}}
<section id="how" class="rise" style="padding-top:0;">
    <div class="wrap">
        <div class="head">
            <h2>{{ $page['both_heading'] }}</h2>
            <p>{{ $page['both_intro'] }}</p>
        </div>
        <div class="steps">
            @foreach ($page['steps'] as $step)
                <div class="step">
                    <div class="step-n"></div>
                    <h3>{{ $step[0] }}</h3>
                    <p>{{ $step[1] }}</p>
                </div>
            @endforeach
        </div>
        <div style="text-align:center; margin-top:30px;">
            <a href="{{ $page['cta_url'] }}" class="btn btn-primary btn-lg" data-cta="compare-how">{{ $page['cta_label'] }}</a>
        </div>
    </div>
</section>

{{-- ============ FAQ ============ --}}
<section id="faq" class="rise">
    <div class="wrap">
        <div class="head"><h2>{{ $page['h1'] }}: common questions</h2></div>
        <div class="faq">
            @foreach ($page['faqs'] as $i => $qa)
                <details class="qa" @if($i === 0) open @endif>
                    <summary>{{ $qa[0] }}</summary>
                    <p>{{ $qa[1] }}</p>
                </details>
            @endforeach
        </div>
    </div>
</section>

{{-- ============ CTA + SOURCES ============ --}}
<section style="padding-top:0;">
    <div class="wrap">
        <div class="cta">
            <h2>Got a WeTransfer you want to keep?</h2>
            <p>Save it into {{ $page['other'] }} before the link expires, without downloading it first.</p>
            <a href="{{ $page['cta_url'] }}" class="btn btn-primary btn-lg" data-cta="compare-footer">{{ $page['cta_label'] }}</a>
        </div>

        <div class="sources" style="margin-top:28px;">
            Sources:
            @foreach ($page['sources'] as $label => $url)
                <a href="{{ $url }}" rel="nofollow noopener" target="_blank">{{ $label }}</a>@if(! $loop->last), @endif
            @endforeach
            &middot; Also see <a href="{{ $page['related'][1] }}">{{ $page['related'][0] }}</a>.
        </div>
    </div>
</section>

@include('partials.site-footer')

<script>
    @include('partials.site-scripts')
</script>

</body>
</html>
