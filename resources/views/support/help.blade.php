<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Help Center - WetoDrive</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">

    <!-- Google Analytics -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-174D73GPWB"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', 'G-174D73GPWB');
    </script>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #2a42f7 0%, #1a2d99 100%);
            min-height: 100vh;
        }

        /* Navigation */
        .navbar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 15px 0;
            position: sticky;
            top: 0;
            z-index: 1000;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }

        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 1.5rem;
            font-weight: bold;
            color: #333;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .nav-links {
            display: flex;
            gap: 30px;
            align-items: center;
        }

        .nav-links a {
            color: #333;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }

        .nav-links a:hover {
            color: #4285f4;
        }

        /* Main Content */
        .main-content {
            padding: 40px 20px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .page-header {
            text-align: center;
            color: white;
            margin-bottom: 40px;
        }

        .page-header h1 {
            font-size: 3rem;
            margin-bottom: 15px;
        }

        .page-header p {
            font-size: 1.2rem;
            opacity: 0.9;
        }

        .help-content {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            padding: 40px;
        }

        .search-box {
            max-width: 600px;
            margin: 0 auto 40px;
        }

        .search-box input {
            width: 100%;
            padding: 15px 20px;
            border: 2px solid #e1e5e9;
            border-radius: 12px;
            font-size: 1.1rem;
            transition: border-color 0.3s;
        }

        .search-box input:focus {
            outline: none;
            border-color: #4285f4;
        }

        .faq-section {
            margin-bottom: 40px;
        }

        .faq-category {
            margin-bottom: 30px;
        }

        .faq-category h2 {
            color: #333;
            margin-bottom: 20px;
            font-size: 1.8rem;
            padding-bottom: 10px;
            border-bottom: 2px solid #e1e5e9;
        }

        .faq-item {
            margin-bottom: 20px;
            border: 1px solid #e1e5e9;
            border-radius: 12px;
            overflow: hidden;
            transition: all 0.3s;
        }

        .faq-item:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .faq-question {
            padding: 20px;
            background: #f8f9fa;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: 600;
            color: #333;
            transition: background 0.3s;
        }

        .faq-question:hover {
            background: #e9ecef;
        }

        .faq-question::after {
            content: '+';
            font-size: 1.5rem;
            color: #4285f4;
            transition: transform 0.3s;
        }

        .faq-item.active .faq-question::after {
            transform: rotate(45deg);
        }

        .faq-answer {
            padding: 0;
            max-height: 0;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .faq-item.active .faq-answer {
            padding: 20px;
            max-height: 500px;
        }

        .faq-answer p {
            color: #666;
            line-height: 1.6;
            margin-bottom: 10px;
        }

        .faq-answer ul {
            margin-left: 20px;
            color: #666;
        }

        .faq-answer li {
            margin-bottom: 8px;
        }

        /* Footer */
        .footer {
            background: #1a1a1a;
            color: white;
            padding: 40px 20px 20px;
            margin-top: 60px;
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
            font-size: 1.1rem;
            color: #fff;
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

        .footer-bottom {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #333;
            color: #808080;
        }

        @media (max-width: 768px) {
            .page-header h1 {
                font-size: 2rem;
            }

            .faq-category h2 {
                font-size: 1.5rem;
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
            <a href="{{ route('home') }}" class="logo">
                📦 WetoDrive
            </a>

            <div class="nav-links">
                <a href="{{ route('home') }}">Home</a>
                <a href="{{ route('subscription.pricing') }}">Pricing</a>
                @auth
                    @if(Auth::user()->isAdmin())
                        <a href="{{ route('admin.dashboard') }}">Admin</a>
                    @else
                        <a href="{{ route('subscription.manage') }}">Dashboard</a>
                    @endif
                @endauth
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="main-content">
        <div class="page-header">
            <h1>❓ Help Center</h1>
            <p>Find answers to common questions about WetoDrive</p>
        </div>

        <div class="help-content">
            <div class="search-box">
                <input type="text" id="searchInput" placeholder="🔍 Search for help..." onkeyup="filterFAQ()">
            </div>

            <div class="faq-section">
                <!-- Getting Started -->
                <div class="faq-category">
                    <h2>🚀 Getting Started</h2>

                    <div class="faq-item">
                        <div class="faq-question" onclick="toggleFAQ(this)">
                            How do I start using WetoDrive?
                        </div>
                        <div class="faq-answer">
                            <p>Getting started with WetoDrive is easy:</p>
                            <ul>
                                <li>Click "Sign In" or "Get Started with Google Drive"</li>
                                <li>Authenticate with your Google account</li>
                                <li>Grant permission to access your Google Drive</li>
                                <li>Paste your WeTransfer URL and click "Transfer to Google Drive"</li>
                            </ul>
                        </div>
                    </div>

                    <div class="faq-item">
                        <div class="faq-question" onclick="toggleFAQ(this)">
                            What types of WeTransfer links are supported?
                        </div>
                        <div class="faq-answer">
                            <p>WetoDrive supports all WeTransfer link formats:</p>
                            <ul>
                                <li>Short links: https://we.tl/t-XXXXXXXXXX</li>
                                <li>Full links: https://wetransfer.com/downloads/...</li>
                                <li>Both free and paid WeTransfer links</li>
                            </ul>
                        </div>
                    </div>

                    <div class="faq-item">
                        <div class="faq-question" onclick="toggleFAQ(this)">
                            Is WetoDrive free to use?
                        </div>
                        <div class="faq-answer">
                            <p>Yes! WetoDrive offers a free tier with:</p>
                            <ul>
                                <li>5 transfers per month</li>
                                <li>Files up to 100MB each</li>
                                <li>No credit card required</li>
                            </ul>
                            <p>For more transfers and larger files, check out our Pro and Premium plans.</p>
                        </div>
                    </div>
                </div>

                <!-- Account & Billing -->
                <div class="faq-category">
                    <h2>💳 Account & Billing</h2>

                    <div class="faq-item">
                        <div class="faq-question" onclick="toggleFAQ(this)">
                            How do I upgrade my subscription?
                        </div>
                        <div class="faq-answer">
                            <p>To upgrade your subscription:</p>
                            <ul>
                                <li>Go to the <a href="{{ route('subscription.pricing') }}">Pricing</a> page</li>
                                <li>Choose your desired plan (Pro or Premium)</li>
                                <li>Click "Subscribe" and complete the payment</li>
                                <li>Your new limits will be activated immediately</li>
                            </ul>
                        </div>
                    </div>

                    <div class="faq-item">
                        <div class="faq-question" onclick="toggleFAQ(this)">
                            What payment methods are accepted?
                        </div>
                        <div class="faq-answer">
                            <p>We accept different payment methods based on your location:</p>
                            <ul>
                                <li><strong>Nigeria:</strong> Paystack (cards, bank transfer, USSD)</li>
                                <li><strong>International:</strong> Credit/debit cards via LemonSqueezy</li>
                            </ul>
                            <p>Payment provider is automatically selected based on your location.</p>
                        </div>
                    </div>

                    <div class="faq-item">
                        <div class="faq-question" onclick="toggleFAQ(this)">
                            How do I cancel my subscription?
                        </div>
                        <div class="faq-answer">
                            <p>To cancel your subscription:</p>
                            <ul>
                                <li>Go to your <a href="{{ route('subscription.manage') }}">Dashboard</a></li>
                                <li>Click "Cancel Subscription"</li>
                                <li>Confirm the cancellation</li>
                                <li>You'll retain access until the end of your billing period</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Technical Issues -->
                <div class="faq-category">
                    <h2>🔧 Troubleshooting</h2>

                    <div class="faq-item">
                        <div class="faq-question" onclick="toggleFAQ(this)">
                            My transfer failed. What should I do?
                        </div>
                        <div class="faq-answer">
                            <p>If your transfer fails, try these steps:</p>
                            <ul>
                                <li>Verify the WeTransfer link is still valid (not expired)</li>
                                <li>Check if the file size is within your plan limits</li>
                                <li>Ensure you haven't exceeded your monthly transfer limit</li>
                                <li>Try refreshing the page and attempting the transfer again</li>
                            </ul>
                            <p>If the issue persists, contact support with your transfer URL.</p>
                        </div>
                    </div>

                    <div class="faq-item">
                        <div class="faq-question" onclick="toggleFAQ(this)">
                            Where are my files saved in Google Drive?
                        </div>
                        <div class="faq-answer">
                            <p>Files are saved to the root folder of your Google Drive by default. You can:</p>
                            <ul>
                                <li>Find them in your main Google Drive folder</li>
                                <li>Move them to any subfolder after transfer</li>
                                <li>Search for them by filename in Google Drive</li>
                            </ul>
                        </div>
                    </div>

                    <div class="faq-item">
                        <div class="faq-question" onclick="toggleFAQ(this)">
                            Why is my file size limit exceeded?
                        </div>
                        <div class="faq-answer">
                            <p>File size limits depend on your subscription plan:</p>
                            <ul>
                                <li><strong>Free:</strong> 100MB per file</li>
                                <li><strong>Pro:</strong> 2GB per file</li>
                                <li><strong>Premium:</strong> 5GB per file</li>
                            </ul>
                            <p>To transfer larger files, consider upgrading your plan.</p>
                        </div>
                    </div>
                </div>

                <!-- Privacy & Security -->
                <div class="faq-category">
                    <h2>🔒 Privacy & Security</h2>

                    <div class="faq-item">
                        <div class="faq-question" onclick="toggleFAQ(this)">
                            Is my data safe with WetoDrive?
                        </div>
                        <div class="faq-answer">
                            <p>Yes, your data is secure:</p>
                            <ul>
                                <li>We use Google's OAuth 2.0 for secure authentication</li>
                                <li>Files stream directly from WeTransfer to Google Drive</li>
                                <li>We don't store your files on our servers</li>
                                <li>All transfers are encrypted using HTTPS</li>
                                <li>We only access the minimum Google Drive permissions needed</li>
                            </ul>
                        </div>
                    </div>

                    <div class="faq-item">
                        <div class="faq-question" onclick="toggleFAQ(this)">
                            What Google Drive permissions do you need?
                        </div>
                        <div class="faq-answer">
                            <p>We request minimal permissions:</p>
                            <ul>
                                <li>Create and upload files to your Google Drive</li>
                                <li>View basic account information (email, name)</li>
                            </ul>
                            <p>We cannot read, modify, or delete your existing files.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-content">
            <div class="footer-section">
                <h4>📦 WetoDrive</h4>
                <a href="{{ route('home') }}">Home</a>
                <a href="{{ route('subscription.pricing') }}">Pricing</a>
                @auth
                    <a href="{{ route('subscription.manage') }}">Dashboard</a>
                @endauth
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
            <p>&copy; {{ date('Y') }} WetoDrive. All rights reserved.</p>
        </div>
    </footer>

    <script>
        function toggleFAQ(element) {
            const faqItem = element.parentElement;
            faqItem.classList.toggle('active');
        }

        function filterFAQ() {
            const input = document.getElementById('searchInput');
            const filter = input.value.toLowerCase();
            const faqItems = document.getElementsByClassName('faq-item');

            for (let i = 0; i < faqItems.length; i++) {
                const question = faqItems[i].getElementsByClassName('faq-question')[0];
                const answer = faqItems[i].getElementsByClassName('faq-answer')[0];
                const textValue = (question.textContent || question.innerText) + (answer.textContent || answer.innerText);

                if (textValue.toLowerCase().indexOf(filter) > -1) {
                    faqItems[i].style.display = "";
                } else {
                    faqItems[i].style.display = "none";
                }
            }
        }
    </script>
</body>
</html>