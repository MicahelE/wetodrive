<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy - WetoDrive</title>
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
            max-width: 900px;
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

        .policy-content {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            padding: 50px 40px;
        }

        .last-updated {
            text-align: center;
            color: #666;
            margin-bottom: 30px;
            font-style: italic;
        }

        .policy-section {
            margin-bottom: 40px;
        }

        .policy-section h2 {
            color: #333;
            margin-bottom: 20px;
            font-size: 1.8rem;
            padding-bottom: 10px;
            border-bottom: 2px solid #e1e5e9;
        }

        .policy-section h3 {
            color: #333;
            margin-bottom: 15px;
            margin-top: 25px;
            font-size: 1.3rem;
        }

        .policy-section p {
            color: #666;
            line-height: 1.8;
            margin-bottom: 15px;
        }

        .policy-section ul {
            margin-left: 30px;
            color: #666;
            line-height: 1.8;
            margin-bottom: 15px;
        }

        .policy-section li {
            margin-bottom: 10px;
        }

        .highlight-box {
            background: #f8f9fa;
            border-left: 4px solid #4285f4;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
        }

        .highlight-box h4 {
            color: #333;
            margin-bottom: 10px;
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

            .policy-content {
                padding: 30px 20px;
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
            <h1>🔒 Privacy Policy</h1>
            <p>How we protect and handle your data</p>
        </div>

        <div class="policy-content">
            <div class="last-updated">Last updated: {{ date('F j, Y') }}</div>

            <div class="policy-section">
                <h2>Introduction</h2>
                <p>
                    WetoDrive ("we", "our", or "us") is committed to protecting your privacy. This Privacy Policy explains how we collect, use, disclose, and safeguard your information when you use our service to transfer files from WeTransfer to Google Drive.
                </p>
                <p>
                    By using WetoDrive, you agree to the collection and use of information in accordance with this policy.
                </p>
            </div>

            <div class="policy-section">
                <h2>Information We Collect</h2>

                <h3>Information from Google Account</h3>
                <p>When you sign in with Google, we collect:</p>
                <ul>
                    <li>Your name</li>
                    <li>Your email address</li>
                    <li>Your Google account ID</li>
                    <li>Google Drive access token (encrypted and stored securely)</li>
                </ul>

                <h3>Transfer Information</h3>
                <p>When you use our service, we collect:</p>
                <ul>
                    <li>WeTransfer URLs you provide</li>
                    <li>File transfer history and statistics</li>
                    <li>Transfer timestamps and status</li>
                </ul>

                <h3>Payment Information</h3>
                <p>For subscriptions, we collect:</p>
                <ul>
                    <li>Subscription plan details</li>
                    <li>Payment transaction IDs</li>
                    <li>Billing country (determined by IP address)</li>
                </ul>
                <p><strong>Note:</strong> We do not store credit card numbers or banking information. All payment processing is handled by secure third-party providers (Paystack or LemonSqueezy).</p>

                <h3>Technical Information</h3>
                <p>We automatically collect:</p>
                <ul>
                    <li>IP address (for geolocation and security)</li>
                    <li>Browser type and version</li>
                    <li>Device information</li>
                    <li>Usage statistics and analytics</li>
                </ul>
            </div>

            <div class="policy-section">
                <h2>How We Use Your Information</h2>
                <p>We use the collected information to:</p>
                <ul>
                    <li>Provide and maintain our file transfer service</li>
                    <li>Authenticate you and manage your account</li>
                    <li>Transfer files from WeTransfer to your Google Drive</li>
                    <li>Process payments and manage subscriptions</li>
                    <li>Send service-related notifications</li>
                    <li>Improve and optimize our service</li>
                    <li>Detect and prevent fraud or abuse</li>
                    <li>Comply with legal obligations</li>
                </ul>
            </div>

            <div class="highlight-box">
                <h4>🔐 Important Security Note</h4>
                <p>We do NOT store the actual files you transfer. Files stream directly from WeTransfer to your Google Drive through our secure servers. We only temporarily process files during the transfer and immediately delete any temporary data after the transfer is complete or if an error occurs.</p>
            </div>

            <div class="policy-section">
                <h2>Google Drive Permissions</h2>
                <p>WetoDrive requests the following Google Drive permissions:</p>
                <ul>
                    <li><strong>Create and upload files:</strong> To save transferred files to your Google Drive</li>
                    <li><strong>View basic account info:</strong> To display your name and email</li>
                </ul>
                <p>We cannot:</p>
                <ul>
                    <li>Read, modify, or delete your existing Google Drive files</li>
                    <li>Access files not created by WetoDrive</li>
                    <li>Share your files with others</li>
                </ul>
            </div>

            <div class="policy-section">
                <h2>Data Sharing and Disclosure</h2>
                <p>We do not sell, trade, or rent your personal information to third parties. We may share your information only in the following circumstances:</p>
                <ul>
                    <li><strong>Service Providers:</strong> With trusted third-party services that help us operate our business (payment processors, analytics)</li>
                    <li><strong>Legal Requirements:</strong> If required by law or to respond to legal process</li>
                    <li><strong>Protection of Rights:</strong> To protect our rights, privacy, safety, or property</li>
                    <li><strong>Business Transfer:</strong> In connection with a merger, acquisition, or sale of assets</li>
                </ul>
            </div>

            <div class="policy-section">
                <h2>Data Retention</h2>
                <p>We retain your information for as long as necessary to provide our services:</p>
                <ul>
                    <li><strong>Account Information:</strong> Retained while your account is active</li>
                    <li><strong>Transfer History:</strong> Retained for service improvement and support</li>
                    <li><strong>Payment Records:</strong> Retained as required for accounting and legal purposes</li>
                    <li><strong>Temporary Files:</strong> Deleted immediately after transfer completion</li>
                </ul>
            </div>

            <div class="policy-section">
                <h2>Data Security</h2>
                <p>We implement industry-standard security measures to protect your information:</p>
                <ul>
                    <li>HTTPS encryption for all data transmission</li>
                    <li>Encrypted storage of sensitive information</li>
                    <li>Regular security audits and updates</li>
                    <li>Limited access to personal information by authorized personnel only</li>
                    <li>Secure OAuth 2.0 authentication with Google</li>
                </ul>
            </div>

            <div class="policy-section">
                <h2>Your Rights and Choices</h2>
                <p>You have the right to:</p>
                <ul>
                    <li><strong>Access:</strong> Request a copy of your personal information</li>
                    <li><strong>Update:</strong> Correct inaccurate or incomplete information</li>
                    <li><strong>Delete:</strong> Request deletion of your account and associated data</li>
                    <li><strong>Disconnect:</strong> Revoke Google Drive access at any time</li>
                    <li><strong>Export:</strong> Receive your data in a portable format</li>
                </ul>
                <p>To exercise these rights, contact us at michael@wetodrive.com</p>
            </div>

            <div class="policy-section">
                <h2>Cookies and Tracking</h2>
                <p>We use cookies and similar tracking technologies to:</p>
                <ul>
                    <li>Maintain your session and authentication</li>
                    <li>Remember your preferences</li>
                    <li>Analyze usage patterns with Google Analytics</li>
                    <li>Improve our service performance</li>
                </ul>
                <p>You can control cookies through your browser settings, but disabling them may limit functionality.</p>
            </div>

            <div class="policy-section">
                <h2>Children's Privacy</h2>
                <p>WetoDrive is not intended for children under 13 years of age. We do not knowingly collect personal information from children under 13. If you believe we have collected information from a child under 13, please contact us immediately.</p>
            </div>

            <div class="policy-section">
                <h2>International Data Transfers</h2>
                <p>Your information may be transferred to and processed in countries other than your own. We ensure appropriate safeguards are in place to protect your information in accordance with this Privacy Policy.</p>
            </div>

            <div class="policy-section">
                <h2>Changes to This Policy</h2>
                <p>We may update our Privacy Policy from time to time. We will notify you of any changes by posting the new Privacy Policy on this page and updating the "Last updated" date.</p>
            </div>

            <div class="policy-section">
                <h2>Contact Us</h2>
                <p>If you have any questions or concerns about this Privacy Policy, please contact us:</p>
                <ul>
                    <li><strong>Email:</strong> michael@wetodrive.com</li>
                    <li><strong>Website:</strong> wetodrive.com</li>
                </ul>
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
</body>
</html>