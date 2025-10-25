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
                'event_label': 'upload_page_' + location,
                'value': 1
            });
        }
    </script>

    <!-- Load Structured Data -->
    <script>
        fetch('{{ asset('js/upload-schema.json') }}')
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
                <a href="{{ route('seo.send-files') }}">Send Files</a>
                <a href="{{ route('seo.free') }}">Free Plan</a>
                <a href="{{ route('auth.google') }}" class="cta-button" onclick="trackCTA('nav')">Try WetoDrive Free</a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container">
        <div class="article-header">
            <h1>How to Upload Files to WeTransfer — and Automatically Save Them to Drive</h1>
            <p class="subtitle">Complete guide to uploading files on WeTransfer, including tips for large files and how WetoDrive automates saving received files to Google Drive.</p>
        </div>

        <div class="content">
            <h2>How to Upload Files to WeTransfer</h2>
            <p>WeTransfer makes uploading and sharing large files simple. Here are the two main methods to upload files:</p>

            <div class="upload-methods">
                <div class="method-card">
                    <h4>📁 Method 1: Click to Upload</h4>
                    <ol>
                        <li>Go to <strong>wetransfer.com</strong></li>
                        <li>Click the <strong>"+" button</strong> in the center</li>
                        <li>Select files from your computer</li>
                        <li>Choose multiple files by holding Ctrl/Cmd</li>
                        <li>Click "Open" to add them</li>
                    </ol>
                </div>

                <div class="method-card">
                    <h4>🖱️ Method 2: Drag and Drop</h4>
                    <ol>
                        <li>Open your file explorer/finder</li>
                        <li>Navigate to your files</li>
                        <li>Select the files you want to upload</li>
                        <li>Drag them to the WeTransfer page</li>
                        <li>Drop them anywhere on the upload area</li>
                    </ol>
                </div>
            </div>

            <div class="tip-box">
                <h4>💡 Pro Tip</h4>
                <p>Drag and drop is often faster for multiple files. You can select entire folders and drag them, but note that WeTransfer will upload individual files, not the folder structure.</p>
            </div>

            <h2>WeTransfer Upload Limits and File Sizes</h2>
            <p>Understanding WeTransfer's limits helps you plan your uploads effectively:</p>

            <div class="file-size-guide">
                <h3>Upload Limits by Plan</h3>
                <ul style="margin: 15px 0; padding-left: 20px;">
                    <li><strong>WeTransfer Free:</strong> Up to 2GB total per transfer</li>
                    <li><strong>WeTransfer Pro:</strong> Up to 20GB total per transfer</li>
                    <li><strong>Individual Files:</strong> No specific limit per file, but total must fit within plan limits</li>
                </ul>

                <h4>Common File Sizes</h4>
                <div class="file-size-examples">
                    <div class="file-example">
                        <div class="icon">📄</div>
                        <div>Documents</div>
                        <div class="size">1-10 MB</div>
                    </div>
                    <div class="file-example">
                        <div class="icon">📸</div>
                        <div>Photos</div>
                        <div class="size">2-20 MB</div>
                    </div>
                    <div class="file-example">
                        <div class="icon">🎵</div>
                        <div>Audio Files</div>
                        <div class="size">3-50 MB</div>
                    </div>
                    <div class="file-example">
                        <div class="icon">🎬</div>
                        <div>Videos</div>
                        <div class="size">50MB-2GB+</div>
                    </div>
                </div>
            </div>

            <h2>Upload Speed and Time Estimates</h2>
            <p>Upload time depends on your internet connection speed and file size:</p>
            <ul style="margin: 20px 0; padding-left: 20px;">
                <li><strong>100 MB file:</strong> 1-3 minutes (typical broadband)</li>
                <li><strong>500 MB file:</strong> 5-10 minutes</li>
                <li><strong>1 GB file:</strong> 10-20 minutes</li>
                <li><strong>2 GB file:</strong> 20-40 minutes</li>
            </ul>

            <div class="warning-box">
                <h4>⚠️ Important Upload Tips</h4>
                <p>Keep your browser tab open during uploads. Closing the tab or losing internet connection will cancel the upload and you'll need to start over.</p>
            </div>

            <h2>Troubleshooting Upload Issues</h2>
            <h3>Common Upload Problems:</h3>
            <ul style="margin: 20px 0; padding-left: 20px;">
                <li><strong>Upload Stuck:</strong> Refresh the page and try again</li>
                <li><strong>Files Too Large:</strong> Compress files or split into multiple transfers</li>
                <li><strong>Slow Upload:</strong> Close other applications using internet</li>
                <li><strong>Browser Issues:</strong> Try a different browser (Chrome works best)</li>
                <li><strong>Connection Drops:</strong> Use a stable wired connection for large files</li>
            </ul>

            <h3>Best Practices for Large File Uploads:</h3>
            <ul style="margin: 20px 0; padding-left: 20px;">
                <li>Upload during off-peak hours for faster speeds</li>
                <li>Compress video files before uploading</li>
                <li>Split very large archives into smaller parts</li>
                <li>Use a stable internet connection</li>
                <li>Don't use other bandwidth-heavy applications during upload</li>
            </ul>

            <div class="highlight-box">
                <h3>🚀 Skip the Download Step</h3>
                <p>Tired of uploading to WeTransfer, then downloading and re-uploading to Google Drive? WetoDrive automates this process completely.</p>
                <a href="{{ route('auth.google') }}" class="cta-button" onclick="trackCTA('highlight_box')" style="background: white; color: #667eea;">Try WetoDrive Free</a>
            </div>

            <h2>The Problem with Traditional WeTransfer Workflow</h2>
            <p>While uploading to WeTransfer is easy, the recipient's experience often involves unnecessary steps:</p>

            <ol style="margin: 20px 0; padding-left: 20px;">
                <li><strong>You upload</strong> files to WeTransfer (time-consuming)</li>
                <li><strong>Recipient downloads</strong> files to their device (uses device storage)</li>
                <li><strong>Recipient uploads</strong> files to their preferred cloud storage</li>
                <li><strong>Files expire</strong> from WeTransfer after 7-28 days</li>
                <li><strong>Device cleanup</strong> required to free up space</li>
            </ol>

            <h2>How WetoDrive Improves the Process</h2>
            <p>WetoDrive eliminates steps 2-5 by automatically saving WeTransfer files directly to Google Drive:</p>

            <ol style="margin: 20px 0; padding-left: 20px;">
                <li><strong>You upload</strong> files to WeTransfer (same as before)</li>
                <li><strong>Recipient uses WetoDrive</strong> to auto-save to Google Drive</li>
                <li><strong>Files stored permanently</strong> in Google Drive</li>
                <li><strong>No device storage used</strong> - files stream directly</li>
                <li><strong>No manual steps</strong> for recipients</li>
            </ol>
        </div>

        <!-- FAQ Section -->
        <div class="faq-section">
            <h2>Frequently Asked Questions</h2>

            <div class="faq-item">
                <h3 class="faq-question">How do I upload files to WeTransfer?</h3>
                <div class="faq-answer">
                    <p>To upload files to WeTransfer: 1) Go to wetransfer.com, 2) Click the '+' button or drag files onto the page, 3) Add recipient emails, 4) Include a message, 5) Click Transfer to upload and send.</p>
                </div>
            </div>

            <div class="faq-item">
                <h3 class="faq-question">What's the maximum file size for WeTransfer uploads?</h3>
                <div class="faq-answer">
                    <p>WeTransfer Free allows uploads up to 2GB total per transfer. WeTransfer Pro increases this limit to 20GB per transfer.</p>
                </div>
            </div>

            <div class="faq-item">
                <h3 class="faq-question">How long do WeTransfer uploads take?</h3>
                <div class="faq-answer">
                    <p>Upload time depends on file size and internet speed. A 1GB file typically takes 5-15 minutes on a standard broadband connection.</p>
                </div>
            </div>

            <div class="faq-item">
                <h3 class="faq-question">Can I upload folders to WeTransfer?</h3>
                <div class="faq-answer">
                    <p>WeTransfer doesn't support direct folder uploads. You need to compress folders into ZIP files first, or select individual files from the folder.</p>
                </div>
            </div>

            <div class="faq-item">
                <h3 class="faq-question">Why do my WeTransfer uploads keep failing?</h3>
                <div class="faq-answer">
                    <p>Common causes include unstable internet connection, files exceeding size limits, or browser issues. Try using Chrome, check your connection, and ensure files are under 2GB (free) or 20GB (Pro).</p>
                </div>
            </div>
        </div>

        <!-- Final CTA -->
        <div class="cta-section">
            <h2>💡 Automate Your File Workflow</h2>
            <p>Stop the upload-download-upload cycle. Use WetoDrive to automatically save WeTransfer files directly to Google Drive.</p>
            <a href="{{ route('auth.google') }}" class="cta-button" onclick="trackCTA('final_cta')">Try WetoDrive Free →</a>
        </div>
    </div>

    <!-- Internal Links -->
    <div class="container">
        <div class="content">
            <h3>More WeTransfer Resources</h3>
            <div class="nav-links">
                <a href="{{ route('seo.pricing') }}">→ WeTransfer Pricing Guide</a>
                <a href="{{ route('seo.send-files') }}">→ How to Send Files</a>
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