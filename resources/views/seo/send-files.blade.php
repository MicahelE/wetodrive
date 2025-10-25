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
                'event_label': 'send_files_page_' + location,
                'value': 1
            });
        }
    </script>

    <!-- Load Structured Data -->
    <script>
        fetch('{{ asset('js/send-files-schema.json') }}')
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
                <a href="{{ route('seo.pricing') }}">Pricing</a>
                <a href="{{ route('seo.upload') }}">Upload Guide</a>
                <a href="{{ route('seo.free') }}">Free Plan</a>
                <a href="{{ route('auth.google') }}" class="cta-button" onclick="trackCTA('nav')">Try WetoDrive Free</a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container">
        <div class="article-header">
            <h1>How to Send Files with WeTransfer — and Save Them Directly to Google Drive</h1>
            <p class="subtitle">Complete step-by-step guide to sending files with WeTransfer, plus how to automatically save received files to Google Drive with WetoDrive.</p>
        </div>

        <div class="content">
            <h2>How to Send Files with WeTransfer (Step-by-Step)</h2>
            <p>WeTransfer makes it easy to send large files that are too big for email. Here's exactly how to use WeTransfer to send files to anyone:</p>

            <div class="step-guide">
                <div class="step">
                    <div class="step-number">1</div>
                    <div class="step-content">
                        <h4>Visit WeTransfer</h4>
                        <p>Go to <strong>wetransfer.com</strong> in your web browser. No account required for basic transfers.</p>
                    </div>
                </div>

                <div class="step">
                    <div class="step-number">2</div>
                    <div class="step-content">
                        <h4>Add Your Files</h4>
                        <p>Click the <strong>"+"</strong> button or simply drag and drop your files onto the page. You can add multiple files up to 2GB total (free plan).</p>
                    </div>
                </div>

                <div class="step">
                    <div class="step-number">3</div>
                    <div class="step-content">
                        <h4>Enter Recipient Email</h4>
                        <p>Type the recipient's email address in the <strong>"Email to"</strong> field. You can add multiple recipients separated by commas.</p>
                    </div>
                </div>

                <div class="step">
                    <div class="step-number">4</div>
                    <div class="step-content">
                        <h4>Add Your Email</h4>
                        <p>Enter your email address in the <strong>"Your email"</strong> field to receive a confirmation when the transfer is complete.</p>
                    </div>
                </div>

                <div class="step">
                    <div class="step-number">5</div>
                    <div class="step-content">
                        <h4>Include a Message (Optional)</h4>
                        <p>Add a personal message to explain what you're sending. This helps recipients understand the content.</p>
                    </div>
                </div>

                <div class="step">
                    <div class="step-number">6</div>
                    <div class="step-content">
                        <h4>Send Your Transfer</h4>
                        <p>Click the <strong>"Transfer"</strong> button. WeTransfer will upload your files and send download links to recipients.</p>
                    </div>
                </div>
            </div>

            <h2>The Problem: What Happens After Sending</h2>
            <p>While sending files with WeTransfer is straightforward, <strong>receiving and managing them</strong> creates friction:</p>

            <div class="comparison-section">
                <div class="workflow-box traditional">
                    <h4>❌ Traditional WeTransfer Workflow</h4>
                    <ul class="workflow-steps">
                        <li>Receive WeTransfer email</li>
                        <li>Click download link</li>
                        <li>Wait for file to download</li>
                        <li>Find file on computer</li>
                        <li>Upload to Google Drive manually</li>
                        <li>Delete file from computer</li>
                        <li>Files expire in 7 days</li>
                    </ul>
                </div>

                <div class="workflow-box wetodrive">
                    <h4>✅ Smart WetoDrive Workflow</h4>
                    <ul class="workflow-steps">
                        <li>Receive WeTransfer email</li>
                        <li>Copy WeTransfer link</li>
                        <li>Paste in WetoDrive</li>
                        <li>Files auto-save to Google Drive</li>
                        <li>No downloads required</li>
                        <li>No device storage used</li>
                        <li>Files saved permanently</li>
                    </ul>
                </div>
            </div>

            <div class="highlight-box">
                <h3>🚀 Automate Your WeTransfer Workflow</h3>
                <p>Stop manually downloading and re-uploading files. Use WetoDrive to automatically save WeTransfer files directly to Google Drive.</p>
                <a href="{{ route('auth.google') }}" class="cta-button" onclick="trackCTA('highlight_box')" style="background: white; color: #667eea;">Try WetoDrive Free</a>
            </div>

            <h2>WeTransfer File Size Limits</h2>
            <p>Understanding WeTransfer's limits helps you choose the right plan:</p>
            <ul style="margin: 20px 0; padding-left: 20px;">
                <li><strong>WeTransfer Free:</strong> Up to 2GB per transfer</li>
                <li><strong>WeTransfer Pro:</strong> Up to 20GB per transfer</li>
                <li><strong>Storage Duration:</strong> 7 days (Free) or 28 days (Pro)</li>
            </ul>

            <h3>What if Files Are Too Large?</h3>
            <p>For files larger than WeTransfer's limits, consider:</p>
            <ul style="margin: 20px 0; padding-left: 20px;">
                <li><strong>Google Drive Direct Sharing:</strong> Share large files directly from Google Drive</li>
                <li><strong>File Compression:</strong> Use tools like WinRAR or 7-Zip to compress files</li>
                <li><strong>Cloud Storage Services:</strong> Dropbox, OneDrive, or other alternatives</li>
            </ul>

            <h2>Best Practices for Sending Files</h2>
            <h3>1. Organize Your Files</h3>
            <p>Before sending, organize files into folders and use descriptive names. This helps recipients understand what they're receiving.</p>

            <h3>2. Include Context</h3>
            <p>Always add a message explaining what you're sending and why. This prevents confusion and helps with organization.</p>

            <h3>3. Notify Recipients</h3>
            <p>Let recipients know to expect your transfer, especially for business files. This ensures they don't miss the email.</p>

            <h3>4. Consider Permanent Storage</h3>
            <p>Remember that WeTransfer files expire. For important documents, recipients should save files to permanent storage immediately.</p>
        </div>

        <!-- CTA Section -->
        <div class="cta-section">
            <h2>💡 Make WeTransfer Work Better</h2>
            <p>Skip the download-and-upload dance. Use WetoDrive to automatically save any WeTransfer files directly to your Google Drive.</p>
            <a href="{{ route('auth.google') }}" class="cta-button" onclick="trackCTA('final_cta')">Try WetoDrive Free →</a>
        </div>
    </div>

    <!-- Internal Links -->
    <div class="container">
        <div class="content">
            <h3>More WeTransfer Guides</h3>
            <div class="nav-links">
                <a href="{{ route('seo.pricing') }}">→ WeTransfer Pricing Guide</a>
                <a href="{{ route('seo.upload') }}">→ How to Upload Files</a>
                <a href="{{ route('seo.free') }}">→ WeTransfer Free Limits</a>
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