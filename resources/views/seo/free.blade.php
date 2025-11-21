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
                'event_label': 'free_page_' + location,
                'value': 1
            });
        }
    </script>

    <!-- Load Structured Data -->
    <script>
        fetch('{{ asset('js/free-schema.json') }}')
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
                <a href="{{ route('seo.upload') }}">Upload Guide</a>
                <a href="{{ route('auth.google') }}" class="cta-button" onclick="trackCTA('nav')">Try WetoDrive Free</a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container">
        <div class="article-header">
            <h1>WeTransfer Free: What You Get, What You Don't — and How to Extend It with WetoDrive</h1>
            <p class="subtitle">Complete breakdown of WeTransfer Free features, limitations, and how WetoDrive helps you keep files permanently in Google Drive.</p>
        </div>

        <div class="content">
            <h2>WeTransfer Free: The Complete Overview</h2>
            <p>WeTransfer's free plan is genuinely useful for basic file sharing, but it comes with important limitations. Here's everything you need to know about what's included and what's not.</p>

            <h2>What's Included in WeTransfer Free</h2>
            <div class="features-grid">
                <div class="feature-card included">
                    <div class="icon">📁</div>
                    <h4>File Transfers</h4>
                    <p>Send files up to 2GB total per transfer with no account required</p>
                </div>

                <div class="feature-card included">
                    <div class="icon">📧</div>
                    <h4>Email Notifications</h4>
                    <p>Recipients get email notifications with download links automatically</p>
                </div>

                <div class="feature-card included">
                    <div class="icon">🌐</div>
                    <h4>Web Interface</h4>
                    <p>Easy-to-use drag-and-drop interface accessible from any browser</p>
                </div>

                <div class="feature-card included">
                    <div class="icon">📱</div>
                    <h4>Mobile Access</h4>
                    <p>Works on mobile devices and tablets through web browser</p>
                </div>

                <div class="feature-card limited">
                    <div class="icon">⏰</div>
                    <h4>7-Day Storage</h4>
                    <p>Files are available for download for 7 days before automatic deletion</p>
                </div>

                <div class="feature-card limited">
                    <div class="icon">💬</div>
                    <h4>Basic Messaging</h4>
                    <p>Include a simple text message with your file transfer</p>
                </div>
            </div>

            <h2>WeTransfer Free Limitations</h2>
            <div class="limitations-box">
                <h3>Important Restrictions to Know</h3>
                <ul class="limitations-list">
                    <li><strong>2GB Maximum:</strong> Total file size per transfer cannot exceed 2GB</li>
                    <li><strong>7-Day Expiry:</strong> Files automatically delete after one week</li>
                    <li><strong>No Password Protection:</strong> Anyone with the link can download files</li>
                    <li><strong>No Transfer History:</strong> Can't track or revisit previous transfers</li>
                    <li><strong>No Custom Backgrounds:</strong> Stuck with default WeTransfer branding</li>
                    <li><strong>No Download Notifications:</strong> Don't know when recipients download files</li>
                    <li><strong>Limited Support:</strong> Basic community support only</li>
                </ul>
            </div>

            <h2>WeTransfer Free vs Pro Comparison</h2>
            <table class="comparison-table">
                <thead>
                    <tr>
                        <th>Feature</th>
                        <th>WeTransfer Free</th>
                        <th>WeTransfer Pro ($12/month)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>File Size Limit</strong></td>
                        <td>2GB per transfer</td>
                        <td>20GB per transfer</td>
                    </tr>
                    <tr>
                        <td><strong>Storage Duration</strong></td>
                        <td>7 days</td>
                        <td>Up to 28 days</td>
                    </tr>
                    <tr>
                        <td><strong>Password Protection</strong></td>
                        <td>❌ Not available</td>
                        <td>✅ Available</td>
                    </tr>
                    <tr>
                        <td><strong>Transfer History</strong></td>
                        <td>❌ Not available</td>
                        <td>✅ Full history</td>
                    </tr>
                    <tr>
                        <td><strong>Custom Backgrounds</strong></td>
                        <td>❌ Default only</td>
                        <td>✅ Custom branding</td>
                    </tr>
                    <tr>
                        <td><strong>Cloud Storage</strong></td>
                        <td>❌ None</td>
                        <td>✅ 100GB included</td>
                    </tr>
                    <tr>
                        <td><strong>Download Notifications</strong></td>
                        <td>❌ Not available</td>
                        <td>✅ Full tracking</td>
                    </tr>
                </tbody>
            </table>

            <h2>The Biggest Problem with WeTransfer Free</h2>
            <p>The most significant limitation isn't the 2GB size limit—it's the <strong>7-day expiration</strong>. Here's why this matters:</p>

            <ul style="margin: 20px 0; padding-left: 20px;">
                <li><strong>Files disappear forever</strong> after one week</li>
                <li><strong>No extension possible</strong>—even if you need files later</li>
                <li><strong>Recipients must act fast</strong> or lose access permanently</li>
                <li><strong>Important documents can be lost</strong> if not downloaded in time</li>
                <li><strong>No backup or recovery</strong> options available</li>
            </ul>

            <div class="solution-box">
                <h3>🚀 Solution: WetoDrive for Permanent Storage</h3>
                <p>Instead of racing against WeTransfer's 7-day clock, use WetoDrive to automatically save files to Google Drive permanently:</p>
                <ul class="solution-benefits">
                    <li>Files saved permanently to your Google Drive</li>
                    <li>No 7-day expiration—keep files forever</li>
                    <li>No device storage used during transfer</li>
                    <li>Automatic organization in Google Drive</li>
                    <li>Access files from anywhere, anytime</li>
                    <li>Free to start—5 transfers included</li>
                </ul>
            </div>

            <h2>Common WeTransfer Free Use Cases</h2>
            <h3>Perfect For:</h3>
            <ul style="margin: 20px 0; padding-left: 20px;">
                <li><strong>Quick one-time shares:</strong> Photos from events, documents for review</li>
                <li><strong>Small file transfers:</strong> Documents under 2GB</li>
                <li><strong>Temporary sharing:</strong> Files recipients will use immediately</li>
                <li><strong>Non-sensitive content:</strong> Public or semi-public files</li>
            </ul>

            <h3>Not Ideal For:</h3>
            <ul style="margin: 20px 0; padding-left: 20px;">
                <li><strong>Important documents:</strong> Risk of losing access after 7 days</li>
                <li><strong>Large projects:</strong> Files over 2GB require multiple transfers</li>
                <li><strong>Ongoing collaboration:</strong> Need access beyond one week</li>
                <li><strong>Sensitive files:</strong> No password protection available</li>
            </ul>

            <div class="highlight-box">
                <h3>💡 Extend WeTransfer Free with Smart Automation</h3>
                <p>Keep using WeTransfer Free for sending, but automatically save received files to Google Drive with WetoDrive. Best of both worlds!</p>
                <a href="{{ route('auth.google') }}" class="cta-button" onclick="trackCTA('highlight_box')" style="background: white; color: #667eea;">Try WetoDrive Free</a>
            </div>

            <h2>How to Maximize WeTransfer Free</h2>
            <h3>Tips for Getting the Most Value:</h3>
            <ol style="margin: 20px 0; padding-left: 20px;">
                <li><strong>Compress files</strong> when approaching the 2GB limit</li>
                <li><strong>Use immediately</strong>—don't wait to download important files</li>
                <li><strong>Inform recipients</strong> about the 7-day deadline</li>
                <li><strong>Split large transfers</strong> into multiple 2GB chunks if needed</li>
                <li><strong>Consider alternatives</strong> for permanent storage needs</li>
            </ol>
        </div>

        <!-- FAQ Section -->
        <div class="faq-section">
            <h2>Frequently Asked Questions</h2>

            <div class="faq-item">
                <h3 class="faq-question">Is WeTransfer completely free?</h3>
                <div class="faq-answer">
                    <p>Yes, WeTransfer offers a completely free plan with no account required. You can send files up to 2GB total per transfer, and files are stored for 7 days.</p>
                </div>
            </div>

            <div class="faq-item">
                <h3 class="faq-question">What are the limitations of WeTransfer free?</h3>
                <div class="faq-answer">
                    <p>WeTransfer Free limits include: 2GB maximum per transfer, 7-day storage duration, no password protection, no transfer history, and no custom backgrounds.</p>
                </div>
            </div>

            <div class="faq-item">
                <h3 class="faq-question">How long do WeTransfer free files last?</h3>
                <div class="faq-answer">
                    <p>Files sent via WeTransfer Free are available for download for 7 days. After this period, they are automatically deleted and cannot be recovered.</p>
                </div>
            </div>

            <div class="faq-item">
                <h3 class="faq-question">Can I extend WeTransfer free storage time?</h3>
                <div class="faq-answer">
                    <p>No, WeTransfer Free files automatically expire after 7 days. To keep files longer, upgrade to WeTransfer Pro (28 days) or use WetoDrive to save files permanently to Google Drive.</p>
                </div>
            </div>

            <div class="faq-item">
                <h3 class="faq-question">Is there a daily limit on WeTransfer free?</h3>
                <div class="faq-answer">
                    <p>WeTransfer doesn't publish specific daily limits for free users, but they do have fair usage policies. For heavy usage, consider upgrading to Pro or using automation tools like WetoDrive.</p>
                </div>
            </div>
        </div>

        <!-- Final CTA -->
        <div class="cta-section">
            <h2>💡 Never Lose WeTransfer Files Again</h2>
            <p>Use WetoDrive to automatically save WeTransfer files to Google Drive permanently. No more 7-day deadlines or lost files.</p>
            <a href="{{ route('auth.google') }}" class="cta-button" onclick="trackCTA('final_cta')">Start Saving Files Forever →</a>
        </div>
    </div>

    <!-- Internal Links -->
    <div class="container">
        <div class="content">
            <h3>Complete WeTransfer Guide</h3>
            <div class="nav-links">
                <a href="{{ route('seo.pricing') }}">→ WeTransfer Pricing Comparison</a>
                <a href="{{ route('seo.send-files') }}">→ How to Send Files</a>
                <a href="{{ route('seo.upload') }}">→ Upload Tutorial</a>
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
                <a href="{{ route('support.help') }}">Help Center</a>
                <a href="{{ route('support.contact') }}">Contact Us</a>
                <a href="{{ route('support.privacy') }}">Privacy Policy</a>
                <a href="{{ route('support.terms') }}">Terms of Service</a>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; {{ date('Y') }} WetoDrive. All rights reserved. Built with ❤️ for seamless file transfers.</p>
        </div>
    </footer>

</body>
</html>