<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $metaData['title'] }}</title>
    <meta name="description" content="{{ $metaData['description'] }}">
    <meta name="keywords" content="{{ $metaData['keywords'] }}">
    <link rel="canonical" href="{{ $metaData['canonical'] }}">

    <!-- Open Graph -->
    <meta property="og:title" content="{{ $metaData['title'] }}">
    <meta property="og:description" content="{{ $metaData['description'] }}">
    <meta property="og:url" content="{{ $metaData['canonical'] }}">
    <meta property="og:type" content="article">
    <meta property="og:site_name" content="WetoDrive">

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $metaData['title'] }}">
    <meta name="twitter:description" content="{{ $metaData['description'] }}">

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">

    <!-- External CSS -->
    <link rel="stylesheet" href="{{ asset('css/seo-pages.css') }}">

    <!-- Google Analytics -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-174D73GPWB"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', 'G-174D73GPWB');

        function trackCTA(location) {
            gtag('event', 'seo_cta_click', {
                'event_category': 'seo',
                'event_label': 'pricing_page_' + location,
                'value': 1
            });
        }
    </script>

    <!-- Load Structured Data -->
    <script>
        fetch('{{ asset('js/pricing-schema.json') }}')
            .then(response => response.json())
            .then(data => {
                const script = document.createElement('script');
                script.type = 'application/ld+json';
                script.textContent = JSON.stringify(data);
                document.head.appendChild(script);
            });
    </script>


</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-container">
            <a href="{{ route('home') }}" class="logo">📦 WetoDrive</a>
            <div class="nav-links">
                <a href="{{ route('home') }}">Home</a>
                <a href="{{ route('subscription.pricing') }}">Pricing</a>
                <a href="{{ route('seo.send-files') }}">Send Files</a>
                <a href="{{ route('seo.upload') }}">Upload Guide</a>
                <a href="{{ route('auth.google') }}" class="cta-button" onclick="trackCTA('nav')">Try WetoDrive Free</a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container">
        <div class="article-header">
            <h1>WeTransfer Pricing (2025): What's Free, What's Paid — and a Smarter Alternative</h1>
            <p class="subtitle">Complete breakdown of WeTransfer costs, features, and how WetoDrive offers a better workflow for automatically saving files to Google Drive.</p>
        </div>

        <div class="content">
            <h2>WeTransfer Pricing Overview</h2>
            <p>WeTransfer offers two main plans: a free version with basic features and a Pro subscription with enhanced capabilities. Here's everything you need to know about WeTransfer pricing in 2025.</p>

            <h3>WeTransfer Free vs Pro Comparison</h3>
            <table class="comparison-table">
                <thead>
                    <tr>
                        <th>Feature</th>
                        <th>WeTransfer Free</th>
                        <th>WeTransfer Pro</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>Price</strong></td>
                        <td>$0/month</td>
                        <td>$12/month or $120/year</td>
                    </tr>
                    <tr>
                        <td><strong>File Size Limit</strong></td>
                        <td>Up to 2GB per transfer</td>
                        <td>Up to 20GB per transfer</td>
                    </tr>
                    <tr>
                        <td><strong>Storage Duration</strong></td>
                        <td>7 days</td>
                        <td>Up to 28 days</td>
                    </tr>
                    <tr>
                        <td><strong>Monthly Transfer Limit</strong></td>
                        <td>No specific limit</td>
                        <td>1TB per month</td>
                    </tr>
                    <tr>
                        <td><strong>Storage Space</strong></td>
                        <td>None (transfer only)</td>
                        <td>100GB cloud storage</td>
                    </tr>
                    <tr>
                        <td><strong>Password Protection</strong></td>
                        <td>❌</td>
                        <td>✅</td>
                    </tr>
                    <tr>
                        <td><strong>Custom Backgrounds</strong></td>
                        <td>❌</td>
                        <td>✅</td>
                    </tr>
                    <tr>
                        <td><strong>Transfer History</strong></td>
                        <td>❌</td>
                        <td>✅</td>
                    </tr>
                </tbody>
            </table>

            <h2>The Problem with WeTransfer's Workflow</h2>
            <p>While WeTransfer is excellent for sending files, receiving and managing files can be cumbersome:</p>
            <ul style="margin: 20px 0; padding-left: 20px;">
                <li><strong>Manual Downloads:</strong> You must download files to your device first</li>
                <li><strong>Limited Storage Time:</strong> Files expire after 7-28 days</li>
                <li><strong>Device Storage:</strong> Large files take up space on your device</li>
                <li><strong>Extra Steps:</strong> Download, then upload to your preferred cloud storage</li>
            </ul>

            <div class="highlight-box">
                <h3>🚀 A Smarter Alternative: WetoDrive</h3>
                <p>Skip the downloads and save WeTransfer files directly to Google Drive automatically. No device storage used, files saved permanently, and it's completely free to start.</p>
                <a href="{{ route('auth.google') }}" class="cta-button" onclick="trackCTA('highlight_box')" style="background: white; color: #667eea;">Try WetoDrive Free</a>
            </div>

            <h2>How WetoDrive Saves You Money</h2>
            <p>Instead of paying $144/year for WeTransfer Pro, use WetoDrive's smart workflow:</p>

            <h3>WetoDrive Benefits:</h3>
            <ul style="margin: 20px 0; padding-left: 20px;">
                <li><strong>Automatic Saving:</strong> Files go straight from WeTransfer to Google Drive</li>
                <li><strong>No Storage Limits:</strong> Use your existing Google Drive space</li>
                <li><strong>Permanent Storage:</strong> Files don't expire after 7 days</li>
                <li><strong>Free to Start:</strong> 5 transfers free, then affordable plans</li>
                <li><strong>No Device Storage:</strong> Files stream directly to Drive</li>
            </ul>

            <h2>WeTransfer Pro vs WetoDrive Pro</h2>
            <table class="comparison-table">
                <thead>
                    <tr>
                        <th>Feature</th>
                        <th>WeTransfer Pro ($144/year)</th>
                        <th>WetoDrive Pro ($X/month)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>File Size Limit</strong></td>
                        <td>20GB per transfer</td>
                        <td>2GB per transfer</td>
                    </tr>
                    <tr>
                        <td><strong>Storage Solution</strong></td>
                        <td>100GB WeTransfer storage</td>
                        <td>Your unlimited Google Drive</td>
                    </tr>
                    <tr>
                        <td><strong>File Expiration</strong></td>
                        <td>28 days maximum</td>
                        <td>Never (stored in Drive)</td>
                    </tr>
                    <tr>
                        <td><strong>Workflow</strong></td>
                        <td>Send → Download → Upload to Drive</td>
                        <td>Send → Auto-save to Drive</td>
                    </tr>
                    <tr>
                        <td><strong>Device Storage Used</strong></td>
                        <td>Yes (during download)</td>
                        <td>None (direct streaming)</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- FAQ Section -->
        <div class="faq-section">
            <h2>Frequently Asked Questions</h2>

            <div class="faq-item">
                <h3 class="faq-question">Is WeTransfer free?</h3>
                <div class="faq-answer">
                    <p>Yes, WeTransfer offers a free plan that allows you to send files up to 2GB and store them for 7 days. However, the free plan has limitations on file size, storage duration, and transfer history.</p>
                </div>
            </div>

            <div class="faq-item">
                <h3 class="faq-question">How much does WeTransfer Pro cost?</h3>
                <div class="faq-answer">
                    <p>WeTransfer Pro costs $12 per month or $120 per year (2025 pricing). It includes 100GB storage, files up to 20GB, 1TB monthly transfers, and longer storage duration up to 28 days.</p>
                </div>
            </div>

            <div class="faq-item">
                <h3 class="faq-question">What's the difference between WeTransfer Free and Pro?</h3>
                <div class="faq-answer">
                    <p>WeTransfer Free allows 2GB file transfers stored for 7 days, while Pro offers 20GB files, 100GB storage, 1TB monthly transfers, password protection, and 28-day storage duration.</p>
                </div>
            </div>

            <div class="faq-item">
                <h3 class="faq-question">Can I connect WeTransfer to Google Drive?</h3>
                <div class="faq-answer">
                    <p>WeTransfer doesn't offer direct Google Drive integration. However, WetoDrive automatically transfers files from WeTransfer links directly to your Google Drive, eliminating the need to download files first.</p>
                </div>
            </div>

            <div class="faq-item">
                <h3 class="faq-question">How long do WeTransfer files stay available?</h3>
                <div class="faq-answer">
                    <p>WeTransfer Free files are available for 7 days, while Pro files can be stored up to 28 days. After this period, files are automatically deleted and cannot be recovered.</p>
                </div>
            </div>
        </div>

        <!-- Final CTA -->
        <div class="cta-section">
            <h2>💡 Skip WeTransfer Downloads Forever</h2>
            <p>Use WetoDrive to automatically save WeTransfer files directly to your Google Drive. No manual downloads, no expired links, no device storage used.</p>
            
        </div>
    </div>

    <!-- Internal Links -->
    <div class="container">
        <div class="content">
            <h3>Learn More About WeTransfer</h3>
            <div class="nav-links">
                <a href="{{ route('seo.send-files') }}">→ How to Send Files with WeTransfer</a>
                <a href="{{ route('seo.upload') }}">→ WeTransfer Upload Guide</a>
                <a href="{{ route('seo.free') }}">→ WeTransfer Free Plan Details</a>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-content">
            <div class="footer-section">
                <h4>📦 WetoDrive</h4>
                <p style="font-size: 0.9rem;">
                    Transfer files from WeTransfer to Google Drive instantly. No downloads, no storage limits on your device.
                </p>
                <div class="producthunt-badge">
                    <a href="https://www.producthunt.com/products/wetodrive?embed=true&utm_source=badge-featured&utm_medium=badge&utm_source=badge-wetodrive" target="_blank">
                        <img src="https://api.producthunt.com/widgets/embed-image/v1/featured.svg?post_id=1029974&theme=light&t=1761306053608" alt="WetoDrive - Automatically save WeTransfer files to Google Drive | Product Hunt" style="width: 250px; height: 54px;" width="250" height="54" />
                    </a>
                </div>
            </div>
            <div class="footer-section">
                <h4>Quick Links</h4>
                <a href="{{ route('home') }}">Home</a>
                <a href="{{ route('subscription.pricing') }}">Pricing</a>
                <a href="{{ route('auth.google') }}">Sign In</a>
            </div>
            <div class="footer-section">
                <h4>WeTransfer Guides</h4>
                <a href="{{ route('seo.pricing') }}">WeTransfer Pricing</a>
                <a href="{{ route('seo.send-files') }}">How to Send Files</a>
                <a href="{{ route('seo.upload') }}">Upload Tutorial</a>
                <a href="{{ route('seo.free') }}">Free Plan Guide</a>
                <a href="{{ route('seo.alternative') }}">WeTransfer Alternative</a>
            </div>
            <div class="footer-section">
                <h4>Support</h4>
                <a href="#" style="pointer-events: none;">Help Center</a>
                <a href="#" style="pointer-events: none;">Contact Us</a>
                <a href="#" style="pointer-events: none;">Privacy Policy</a>
                <a href="#" style="pointer-events: none;">Terms of Service</a>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; {{ date('Y') }} WetoDrive. All rights reserved. Built with ❤️ for seamless file transfers.</p>
        </div>
    </footer>

</body>
</html>
