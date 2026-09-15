{{-- ============ FOOTER ============ --}}
<footer>
    <div class="wrap">
        <div class="foot">
            <div class="foot-about">
                <a href="{{ route('home') }}" class="brand" style="color:#fff;">
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
                @else <a href="{{ route('auth.google') }}" data-cta="footer-link">Sign In</a> @endauth
            </div>

            <div>
                <h4>WeTransfer Guides</h4>
                <a href="{{ route('seo.pricing') }}">WeTransfer Pricing</a>
                <a href="{{ route('seo.send-files') }}">How to Send Files</a>
                <a href="{{ route('seo.upload') }}">Upload Tutorial</a>
                <a href="{{ route('seo.free') }}">Free Plan Guide</a>
                <a href="{{ route('seo.alternative') }}">WeTransfer Alternative</a>
                <a href="{{ route('seo.google-drive-guide') }}">Save to Google Drive</a>
                @if(config('services.dropbox.client_id'))
                    <a href="{{ route('dropbox') }}">WeTransfer to Dropbox</a>
                @endif
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
