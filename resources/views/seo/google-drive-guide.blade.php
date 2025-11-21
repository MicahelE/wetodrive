<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $metaData['title'] ?? 'How to Save Files to Google Drive from WeTransfer - Complete Guide 2025' }}</title>
    <meta name="description" content="{{ $metaData['description'] ?? 'Learn how to automatically save WeTransfer files directly to Google Drive. Step-by-step guide using WetoDrive for instant file transfers without downloading.' }}">
    <meta name="keywords" content="{{ $metaData['keywords'] ?? 'save files to google drive, wetransfer to google drive, transfer files to google drive' }}">
    <link rel="canonical" href="{{ $metaData['canonical'] ?? url()->current() }}">

    <!-- Open Graph -->
    <meta property="og:title" content="{{ $metaData['title'] ?? 'How to Save Files to Google Drive from WeTransfer' }}">
    <meta property="og:description" content="{{ $metaData['description'] ?? 'Automatic file transfer from WeTransfer to Google Drive' }}">
    <meta property="og:type" content="article">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:site_name" content="WetoDrive">

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $metaData['title'] ?? 'How to Save Files to Google Drive from WeTransfer' }}">
    <meta name="twitter:description" content="{{ $metaData['description'] ?? 'Automatic file transfer from WeTransfer to Google Drive' }}">

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
                'event_label': 'google_drive_guide_' + location,
                'value': 1
            });
        }
    </script>

    <!-- Load Structured Data -->
    <script>
        fetch('{{ asset('js/google-drive-schema.json') }}')
            .then(response => response.json())
            .then(data => {
                const script = document.createElement('script');
                script.type = 'application/ld+json';
                script.textContent = JSON.stringify(data);
                document.head.appendChild(script);
            });
    </script>

    <style>
        /* Custom styles for Google Drive Guide page */

        /* Hero Section */
        .hero-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 80px 20px;
            text-align: center;
        }

        .hero-content {
            max-width: 900px;
            margin: 0 auto;
        }

        .hero-content h1 {
            font-size: 3rem;
            margin-bottom: 20px;
            line-height: 1.2;
        }

        .hero-content p {
            font-size: 1.3rem;
            margin-bottom: 30px;
            opacity: 0.95;
        }

        .cta-buttons {
            display: flex;
            gap: 20px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn-primary {
            background: white;
            color: #667eea;
            padding: 15px 30px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }

        .btn-secondary {
            background: transparent;
            color: white;
            border: 2px solid white;
            padding: 15px 30px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s;
        }

        .btn-secondary:hover {
            background: white;
            color: #667eea;
        }

        /* Main Content */
        .main-content {
            max-width: 1000px;
            margin: 0 auto;
            padding: 60px 20px;
        }

        .content-section {
            background: white;
            border-radius: 12px;
            padding: 40px;
            margin-bottom: 40px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }

        .content-section h2 {
            color: #333;
            font-size: 2.2rem;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 3px solid #4285f4;
        }

        .content-section h3 {
            color: #333;
            font-size: 1.5rem;
            margin: 30px 0 15px;
        }

        .content-section p {
            color: #666;
            line-height: 1.8;
            margin-bottom: 20px;
        }

        .step-number {
            background: #4285f4;
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 15px;
        }

        .step-item {
            display: flex;
            align-items: start;
            margin-bottom: 25px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
            transition: all 0.3s;
        }

        .step-item:hover {
            background: #e9ecef;
            transform: translateX(5px);
        }

        .step-content {
            flex: 1;
        }

        .step-content h4 {
            color: #333;
            margin-bottom: 10px;
            font-size: 1.2rem;
        }

        .comparison-table {
            width: 100%;
            border-collapse: collapse;
            margin: 30px 0;
        }

        .comparison-table th {
            background: #4285f4;
            color: white;
            padding: 15px;
            text-align: left;
        }

        .comparison-table td {
            padding: 15px;
            border-bottom: 1px solid #e9ecef;
        }

        .comparison-table tr:hover {
            background: #f8f9fa;
        }

        .feature-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
            margin: 30px 0;
        }

        .feature-card {
            background: #f8f9fa;
            padding: 30px;
            border-radius: 12px;
            text-align: center;
            transition: all 0.3s;
        }

        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .feature-icon {
            font-size: 3rem;
            margin-bottom: 15px;
        }

        .feature-card h4 {
            color: #333;
            margin-bottom: 10px;
        }

        .feature-card p {
            color: #666;
            font-size: 0.95rem;
        }

        .highlight-box {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 12px;
            margin: 40px 0;
            text-align: center;
        }

        .highlight-box h3 {
            font-size: 1.8rem;
            margin-bottom: 15px;
        }

        .highlight-box p {
            font-size: 1.1rem;
            margin-bottom: 20px;
        }

        .faq-item {
            margin-bottom: 20px;
            border: 1px solid #e9ecef;
            border-radius: 10px;
            overflow: hidden;
        }

        .faq-question {
            padding: 20px;
            background: #f8f9fa;
            cursor: pointer;
            font-weight: 600;
            color: #333;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .faq-question:hover {
            background: #e9ecef;
        }

        .faq-answer {
            padding: 0 20px;
            max-height: 0;
            overflow: hidden;
            transition: all 0.3s;
        }

        .faq-item.active .faq-answer {
            padding: 20px;
            max-height: 500px;
        }

        /* Footer */
        .footer {
            background: #1a1a1a;
            color: white;
            padding: 40px 20px 20px;
        }

        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
        }

        .footer-section h4 {
            margin-bottom: 15px;
        }

        .footer-section a {
            color: #b0b0b0;
            text-decoration: none;
            display: block;
            margin-bottom: 8px;
            transition: color 0.3s;
        }

        .footer-section a:hover {
            color: #4285f4;
        }

        @media (max-width: 768px) {
            .hero-content h1 {
                font-size: 2rem;
            }

            .content-section {
                padding: 25px 20px;
            }

            .comparison-table {
                font-size: 0.9rem;
            }

            .nav-links {
                display: none;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-container">
            <a href="{{ route('home') }}" class="logo">📦 WetoDrive</a>
            <div class="nav-links">
                <a href="{{ route('home') }}">Home</a>
                <a href="{{ route('subscription.pricing') }}">Pricing</a>
                <a href="{{ route('auth.google') }}" class="cta-button" onclick="trackCTA('nav')">Try WetoDrive Free</a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <div class="hero-section">
        <div class="hero-content">
            <h1>How to Save Files to Google Drive from WeTransfer</h1>
            <p>The Complete 2025 Guide to Automatic File Transfers Without Downloads</p>
            <div class="cta-buttons">
                <a href="{{ route('auth.google') }}" class="btn-primary">
                    🚀 Start Saving Files Now
                </a>
                <a href="#how-it-works" class="btn-secondary">Learn How It Works</a>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Introduction -->
        <div class="content-section">
            <h2>Save WeTransfer Files Directly to Google Drive in 2025</h2>
            <p>
                <strong>Tired of the download-upload dance?</strong> You receive a WeTransfer link, download the file to your computer, then manually upload it to Google Drive. This process wastes time, consumes bandwidth, and clutters your local storage.
            </p>
            <p>
                <strong>WetoDrive changes everything.</strong> Our service automatically transfers files from WeTransfer directly to your Google Drive—no downloads, no uploads, no storage worries. Just paste the link and watch the magic happen.
            </p>

            <div class="feature-grid">
                <div class="feature-card">
                    <div class="feature-icon">⚡</div>
                    <h4>Instant Transfer</h4>
                    <p>Files go directly from WeTransfer to Google Drive in seconds</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">💾</div>
                    <h4>Zero Storage Used</h4>
                    <p>No need to download files to your device first</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">🔄</div>
                    <h4>Automatic Process</h4>
                    <p>Set it and forget it—we handle everything</p>
                </div>
            </div>
        </div>

        <!-- How It Works -->
        <div class="content-section" id="how-it-works">
            <h2>How to Save Files to Google Drive: Step-by-Step Guide</h2>

            <div class="step-item">
                <span class="step-number">1</span>
                <div class="step-content">
                    <h4>Connect Your Google Drive</h4>
                    <p>Sign in with your Google account to give WetoDrive permission to save files to your Drive. We only request minimal permissions needed to create and upload files.</p>
                </div>
            </div>

            <div class="step-item">
                <span class="step-number">2</span>
                <div class="step-content">
                    <h4>Copy Your WeTransfer Link</h4>
                    <p>Get the WeTransfer download link from your email or message. We support all WeTransfer URL formats, including short links (we.tl) and full URLs.</p>
                </div>
            </div>

            <div class="step-item">
                <span class="step-number">3</span>
                <div class="step-content">
                    <h4>Paste and Transfer</h4>
                    <p>Paste the WeTransfer URL into WetoDrive and click "Transfer to Google Drive". The file will be automatically saved to your Drive's root folder.</p>
                </div>
            </div>

            <div class="step-item">
                <span class="step-number">4</span>
                <div class="step-content">
                    <h4>Access Your Files</h4>
                    <p>Find your transferred files instantly in Google Drive. They're permanently stored, organized, and accessible from any device.</p>
                </div>
            </div>
        </div>

        <!-- Comparison Section -->
        <div class="content-section">
            <h2>Manual Method vs WetoDrive: The Clear Winner</h2>

            <table class="comparison-table">
                <thead>
                    <tr>
                        <th>Feature</th>
                        <th>Manual Download & Upload</th>
                        <th>WetoDrive Automatic</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>Time Required</strong></td>
                        <td>5-15 minutes per file</td>
                        <td>30 seconds total</td>
                    </tr>
                    <tr>
                        <td><strong>Steps Involved</strong></td>
                        <td>Download → Find file → Upload → Wait</td>
                        <td>Paste link → Click transfer</td>
                    </tr>
                    <tr>
                        <td><strong>Local Storage</strong></td>
                        <td>Uses device storage temporarily</td>
                        <td>Zero storage needed</td>
                    </tr>
                    <tr>
                        <td><strong>Internet Bandwidth</strong></td>
                        <td>Download + Upload (2x usage)</td>
                        <td>Direct transfer (1x usage)</td>
                    </tr>
                    <tr>
                        <td><strong>Large Files (>1GB)</strong></td>
                        <td>Often fails or times out</td>
                        <td>Handles up to 5GB seamlessly</td>
                    </tr>
                    <tr>
                        <td><strong>Automation</strong></td>
                        <td>Manual every time</td>
                        <td>Fully automated</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Benefits Section -->
        <div class="content-section">
            <h2>Why Save Files to Google Drive with WetoDrive?</h2>

            <h3>🚀 Speed and Efficiency</h3>
            <p>
                Transfer files 10x faster than the manual method. While traditional downloading and uploading can take 10-15 minutes for large files, WetoDrive completes the entire process in under a minute.
            </p>

            <h3>💾 Preserve Device Storage</h3>
            <p>
                Never worry about running out of space on your phone or computer. Files stream directly to Google Drive without touching your device storage—perfect for mobile users or those with limited disk space.
            </p>

            <h3>🔒 Enhanced Security</h3>
            <p>
                Files transfer through encrypted connections directly between services. No files are stored on our servers—they stream through securely and are immediately saved to your private Google Drive.
            </p>

            <h3>📱 Works on Any Device</h3>
            <p>
                Use WetoDrive on your phone, tablet, or computer. Since everything happens in the cloud, you can transfer massive files even on devices with minimal storage or slow processors.
            </p>

            <h3>♾️ Permanent Storage</h3>
            <p>
                WeTransfer links expire after 7-30 days, but files saved to Google Drive are yours forever. Never lose important files because you forgot to download them before the link expired.
            </p>
        </div>

        <!-- Use Cases -->
        <div class="content-section">
            <h2>Perfect for Every Scenario</h2>

            <div class="feature-grid">
                <div class="feature-card">
                    <div class="feature-icon">👔</div>
                    <h4>Business Professionals</h4>
                    <p>Receive client files, proposals, and documents directly to your organized Drive folders</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">🎨</div>
                    <h4>Creative Teams</h4>
                    <p>Transfer design files, videos, and creative assets without maxing out your SSD</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">🎓</div>
                    <h4>Students & Educators</h4>
                    <p>Save course materials, assignments, and research papers directly to Drive for easy access</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">📸</div>
                    <h4>Photographers</h4>
                    <p>Transfer high-resolution photo shoots directly to cloud storage for clients</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">🎬</div>
                    <h4>Video Editors</h4>
                    <p>Move large video files without downloading to your already-full editing workstation</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">🏠</div>
                    <h4>Personal Use</h4>
                    <p>Save family photos, important documents, and memories safely in the cloud</p>
                </div>
            </div>
        </div>

        <!-- CTA Section -->
        <div class="highlight-box">
            <h3>Start Saving Files to Google Drive Automatically</h3>
            <p>Join thousands of users who've eliminated the download-upload hassle forever</p>
            <a href="{{ route('auth.google') }}" class="btn-primary">
                🚀 Get Started Free - No Credit Card Required
            </a>
        </div>

        <!-- FAQ Section -->
        <div class="content-section">
            <h2>Frequently Asked Questions</h2>

            <div class="faq-item">
                <div class="faq-question" onclick="toggleFAQ(this)">
                    Is it safe to connect my Google Drive to WetoDrive?
                    <span>+</span>
                </div>
                <div class="faq-answer">
                    <p>Absolutely! We use Google's official OAuth 2.0 authentication, meaning we never see your password. We only request permission to create and upload files—we cannot read, modify, or delete your existing files. You can revoke access anytime from your Google account settings.</p>
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question" onclick="toggleFAQ(this)">
                    What's the maximum file size I can transfer?
                    <span>+</span>
                </div>
                <div class="faq-answer">
                    <p>File size limits depend on your plan: Free users can transfer files up to 100MB, Pro users up to 2GB, and Premium users up to 5GB per file. There are also monthly transfer limits: 5 for Free, 100 for Pro, and unlimited for Premium users.</p>
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question" onclick="toggleFAQ(this)">
                    Can I organize files into specific Google Drive folders?
                    <span>+</span>
                </div>
                <div class="faq-answer">
                    <p>Currently, files are saved to your Google Drive's root folder. After the transfer completes, you can easily move them to any folder within Google Drive. We're working on adding folder selection in a future update.</p>
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question" onclick="toggleFAQ(this)">
                    What happens if the WeTransfer link expires during transfer?
                    <span>+</span>
                </div>
                <div class="faq-answer">
                    <p>Our system works quickly to prevent this, but if a link expires mid-transfer, you'll receive an error message. Always initiate transfers as soon as you receive the WeTransfer link for best results. Most transfers complete in under 60 seconds.</p>
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question" onclick="toggleFAQ(this)">
                    Do you store my files on your servers?
                    <span>+</span>
                </div>
                <div class="faq-answer">
                    <p>No, we never permanently store your files. Files stream directly from WeTransfer through our secure servers to your Google Drive. Any temporary data is immediately deleted after the transfer completes or if an error occurs.</p>
                </div>
            </div>
        </div>

        <!-- Technical Guide -->
        <div class="content-section">
            <h2>Advanced Tips for Power Users</h2>

            <h3>📋 Batch Processing</h3>
            <p>Have multiple WeTransfer links? Save time by opening multiple browser tabs and initiating transfers simultaneously. Our system handles concurrent transfers efficiently.</p>

            <h3>🔗 Bookmark for Quick Access</h3>
            <p>Add WetoDrive to your browser bookmarks or mobile home screen for instant access when you receive WeTransfer links.</p>

            <h3>📊 Track Your Usage</h3>
            <p>Monitor your transfer history and remaining quota in your dashboard. Upgrade anytime if you need more transfers or larger file limits.</p>

            <h3>🌍 Global Performance</h3>
            <p>Our service works worldwide with optimized servers for fast transfers regardless of your location or the file source.</p>
        </div>

        <!-- Final CTA -->
        <div class="content-section" style="text-align: center; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
            <h2 style="color: white; border-bottom-color: white;">Ready to Save Files to Google Drive the Smart Way?</h2>
            <p style="color: white; font-size: 1.2rem; margin-bottom: 30px;">
                Stop wasting time with downloads and uploads. Start transferring files directly to Google Drive with WetoDrive.
            </p>
            <div class="cta-buttons">
                <a href="{{ route('auth.google') }}" class="btn-primary">
                    🎯 Start Free with 5 Transfers
                </a>
                <a href="{{ route('subscription.pricing') }}" class="btn-secondary">
                    View Pricing Plans
                </a>
            </div>
            <p style="color: white; margin-top: 20px; opacity: 0.9;">
                No credit card required • Set up in 30 seconds • Cancel anytime
            </p>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-content">
            <div class="footer-section">
                <h4>📦 WetoDrive</h4>
                <a href="{{ route('home') }}">Home</a>
                <a href="{{ route('subscription.pricing') }}">Pricing</a>
            </div>
            <div class="footer-section">
                <h4>WeTransfer Guides</h4>
                <a href="{{ route('seo.pricing') }}">WeTransfer Pricing</a>
                <a href="{{ route('seo.send-files') }}">How to Send Files</a>
                <a href="{{ route('seo.upload') }}">Upload Tutorial</a>
                <a href="{{ route('seo.free') }}">Free Plan Guide</a>
                <a href="{{ route('seo.alternative') }}">WeTransfer Alternative</a>
                <a href="{{ route('seo.google-drive-guide') }}">Save to Google Drive</a>
            </div>
            <div class="footer-section">
                <h4>Support</h4>
                <a href="{{ route('support.help') }}">Help Center</a>
                <a href="{{ route('support.contact') }}">Contact Us</a>
                <a href="{{ route('support.privacy') }}">Privacy Policy</a>
                <a href="{{ route('support.terms') }}">Terms of Service</a>
            </div>
        </div>
    </footer>

    <script>
        function toggleFAQ(element) {
            const faqItem = element.parentElement;
            faqItem.classList.toggle('active');
        }
    </script>
</body>
</html>